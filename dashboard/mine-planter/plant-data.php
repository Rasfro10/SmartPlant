<?php
// Function to get the latest sensor data for a plant
function getLatestSensorData($plant_id, $conn)
{
    $data = null;

    $sql = "SELECT * FROM plant_data 
            WHERE plant_id = ? 
            ORDER BY reading_time DESC 
            LIMIT 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $plant_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $data = $row;
            }
        }

        $stmt->close();
    }

    return $data;
}

// Function to get sensor data history for charts
function getSensorDataHistory($plant_id, $conn, $days = 7)
{
    $data = [];

    $sql = "SELECT DATE(reading_time) as date, 
                   AVG(soil_moisture) as avg_soil_moisture,
                   AVG(light_level) as avg_light_level,
                   AVG(temperature) as avg_temperature,
                   AVG(humidity) as avg_humidity
            FROM plant_data 
            WHERE plant_id = ? 
                AND reading_time >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(reading_time)
            ORDER BY date";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $plant_id, $days);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        $stmt->close();
    }

    return $data;
}

// Function to determine plant status based on sensor data
function getPlantStatus($sensorData, $plant)
{
    $status = [
        'status' => 'healthy',  // Default status
        'message' => 'Planten ser sund ud!',
        'issues' => []
    ];

    // If we have sensor data
    if ($sensorData) {
        // Check soil moisture
        if ($sensorData['soil_moisture'] < 20) {
            $status['status'] = 'needs_water';
            $status['issues'][] = 'Lav jordfugtighed';
        }

        // Check light level
        $lightNeeds = $plant['light_needs'];
        $lightLevel = $sensorData['light_level'];

        if ($lightNeeds == 'Højt' && $lightLevel < 1000) {
            $status['status'] = 'needs_light';
            $status['issues'][] = 'For lidt lys for denne plante';
        } elseif ($lightNeeds == 'Medium' && $lightLevel < 500) {
            $status['status'] = 'needs_light';
            $status['issues'][] = 'For lidt lys for denne plante';
        } elseif ($lightNeeds == 'Lavt' && $lightLevel < 200) {
            $status['status'] = 'needs_light';
            $status['issues'][] = 'For lidt lys for denne plante';
        }

        // Check temperature (generalized for most indoor plants)
        if ($sensorData['temperature'] < 10 || $sensorData['temperature'] > 30) {
            $status['issues'][] = 'Temperaturen er ikke ideel';
        }

        // Update overall status message
        if (count($status['issues']) > 0) {
            $status['message'] = 'Planten behøver opmærksomhed!';
        }
    } else {
        $status['message'] = 'Ingen sensordata tilgængelig';
    }

    return $status;
}

?>

<?php if ($sensorData): ?>
    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Plantesensor Data</h3>

        <!-- Status indicator -->
        <div class="mb-4 p-3 rounded-lg <?php echo $plantStatus['status'] == 'healthy' ? 'bg-green-100' : 'bg-yellow-100'; ?>">
            <div class="flex items-center">
                <div class="h-10 w-10 rounded-full <?php echo $plantStatus['status'] == 'healthy' ? 'bg-green-200' : 'bg-yellow-200'; ?> flex items-center justify-center mr-3">
                    <i class="fas <?php echo $plantStatus['status'] == 'healthy' ? 'fa-check' : 'fa-exclamation-triangle'; ?> <?php echo $plantStatus['status'] == 'healthy' ? 'text-green-600' : 'text-yellow-600'; ?>"></i>
                </div>
                <div>
                    <p class="font-medium"><?php echo $plantStatus['message']; ?></p>
                    <?php if (count($plantStatus['issues']) > 0): ?>
                        <ul class="text-sm mt-1">
                            <?php foreach ($plantStatus['issues'] as $issue): ?>
                                <li>• <?php echo $issue; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Current readings -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-3 rounded-lg">
                <div class="flex items-center mb-1">
                    <i class="fas fa-tint text-blue-500 mr-2"></i>
                    <span class="font-medium">Jordfugtighed</span>
                </div>
                <p class="text-2xl font-semibold"><?php echo number_format($sensorData['soil_moisture'], 1); ?>%</p>
                <p class="text-xs text-gray-500">Sidst opdateret: <?php echo date('d/m H:i', strtotime($sensorData['reading_time'])); ?></p>
            </div>

            <div class="bg-yellow-50 p-3 rounded-lg">
                <div class="flex items-center mb-1">
                    <i class="fas fa-sun text-yellow-500 mr-2"></i>
                    <span class="font-medium">Lysniveau</span>
                </div>
                <p class="text-2xl font-semibold"><?php echo number_format($sensorData['light_level'], 0); ?> lux</p>
                <p class="text-xs text-gray-500">Sidst opdateret: <?php echo date('d/m H:i', strtotime($sensorData['reading_time'])); ?></p>
            </div>

            <div class="bg-red-50 p-3 rounded-lg">
                <div class="flex items-center mb-1">
                    <i class="fas fa-temperature-high text-red-500 mr-2"></i>
                    <span class="font-medium">Temperatur</span>
                </div>
                <p class="text-2xl font-semibold"><?php echo number_format($sensorData['temperature'], 1); ?>°C</p>
                <p class="text-xs text-gray-500">Sidst opdateret: <?php echo date('d/m H:i', strtotime($sensorData['reading_time'])); ?></p>
            </div>

            <div class="bg-indigo-50 p-3 rounded-lg">
                <div class="flex items-center mb-1">
                    <i class="fas fa-cloud text-indigo-500 mr-2"></i>
                    <span class="font-medium">Luftfugtighed</span>
                </div>
                <p class="text-2xl font-semibold"><?php echo number_format($sensorData['humidity'], 1); ?>%</p>
                <p class="text-xs text-gray-500">Sidst opdateret: <?php echo date('d/m H:i', strtotime($sensorData['reading_time'])); ?></p>
            </div>
        </div>

        <!-- Chart -->
        <?php if (count($historyData) > 0): ?>
            <div>
                <h4 class="text-md font-medium text-gray-700 mb-3">Seneste 7 dages historie</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <canvas id="sensorChart" width="400" height="200"></canvas>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                // Create chart with sensor history data
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('sensorChart').getContext('2d');

                    // Prepare data for chart
                    const dates = <?php echo json_encode(array_column($historyData, 'date')); ?>;
                    const soilData = <?php echo json_encode(array_column($historyData, 'avg_soil_moisture')); ?>;
                    const tempData = <?php echo json_encode(array_column($historyData, 'avg_temperature')); ?>;
                    const humidityData = <?php echo json_encode(array_column($historyData, 'avg_humidity')); ?>;

                    const sensorChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: dates,
                            datasets: [{
                                    label: 'Jordfugtighed (%)',
                                    data: soilData,
                                    borderColor: 'rgba(59, 130, 246, 1)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    tension: 0.1
                                },
                                {
                                    label: 'Temperatur (°C)',
                                    data: tempData,
                                    borderColor: 'rgba(239, 68, 68, 1)',
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    tension: 0.1
                                },
                                {
                                    label: 'Luftfugtighed (%)',
                                    data: humidityData,
                                    borderColor: 'rgba(79, 70, 229, 1)',
                                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                    tension: 0.1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: false
                                }
                            }
                        }
                    });
                });
            </script>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mb-6">
        <div class="flex items-center">
            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                <i class="fas fa-seedling text-gray-500"></i>
            </div>
            <div>
                <p class="font-medium text-gray-700">Ingen sensordata tilgængelig endnu</p>
                <p class="text-sm text-gray-500">Tilslut en plante-sensor for at overvåge plantens tilstand.</p>
            </div>
        </div>
    </div>
<?php endif; ?>