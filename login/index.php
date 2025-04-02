<?php
// Start session
session_start();

// Inkluder database forbindelsen
require_once '../db/db_conn.php';

// Initialiser variabler
$email = $password = "";
$email_err = $password_err = $signup_err = $login_err = "";
$signup_success = false;

// Behandle login-formular
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {

    // Valider email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Indtast venligst en email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Valider password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Indtast venligst din adgangskode.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Tjek for fejl inden login
    if (empty($email_err) && empty($password_err)) {
        // Forbered SELECT statement
        $sql = "SELECT id, email, password FROM users WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variabler til prepared statement
            $stmt->bind_param("s", $param_email);

            // Set parametre
            $param_email = $email;

            // Forsøg at eksekvere prepared statement
            if ($stmt->execute()) {
                // Gem resultat
                $stmt->store_result();

                // Tjek om brugeren findes
                if ($stmt->num_rows == 1) {
                    // Bind resultat variabler
                    $stmt->bind_result($id, $email, $hashed_password);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password er korrekt, start en ny session

                            // Gem data i session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["email"] = $email;

                            // Set cookie for session persistence if remember me is checked
                            if (isset($_POST["remember-me"])) {
                                // Set cookies to last for 30 days
                                setcookie("user_login", $email, time() + (86400 * 30), "/");
                                setcookie("user_id", $id, time() + (86400 * 30), "/");
                                // Note: Do not store passwords in cookies for security reasons
                            }

                            // Redirect til dashboard
                            header("location: ../dashboard/");
                            exit;
                        } else {
                            // Password er ikke korrekt
                            $login_err = "Ugyldig email eller adgangskode.";
                        }
                    }
                } else {
                    // Brugeren findes ikke
                    $login_err = "Ugyldig email eller adgangskode.";
                }
            } else {
                echo "Oops! Noget gik galt. Prøv igen senere.";
            }

            // Luk statement
            $stmt->close();
        }
    }
}

// Behandle registreringsformular
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {

    // Valider fornavn
    if (empty(trim($_POST["first-name"]))) {
        $signup_err = "Indtast venligst dit fornavn.";
    }

    // Valider efternavn
    if (empty(trim($_POST["last-name"])) && empty($signup_err)) {
        $signup_err = "Indtast venligst dit efternavn.";
    }

    // Valider email
    if (empty(trim($_POST["email"])) && empty($signup_err)) {
        $signup_err = "Indtast venligst en email.";
    } else {
        // Forbered SELECT statement for at tjekke om emailen allerede eksisterer
        $sql = "SELECT id FROM users WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variabler til prepared statement
            $stmt->bind_param("s", $param_email);

            // Set parametre
            $param_email = trim($_POST["email"]);

            // Forsøg at eksekvere prepared statement
            if ($stmt->execute()) {
                // Gem resultat
                $stmt->store_result();

                if ($stmt->num_rows == 1 && empty($signup_err)) {
                    $signup_err = "Denne email er allerede i brug.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Oops! Noget gik galt. Prøv igen senere.";
            }

            // Luk statement
            $stmt->close();
        }
    }

    // Valider password
    if (empty(trim($_POST["password"])) && empty($signup_err)) {
        $signup_err = "Indtast venligst en adgangskode.";
    } elseif (strlen(trim($_POST["password"])) < 6 && empty($signup_err)) {
        $signup_err = "Adgangskoden skal være mindst 6 tegn.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Valider password bekræftelse
    if (empty(trim($_POST["password-confirm"])) && empty($signup_err)) {
        $signup_err = "Bekræft venligst adgangskoden.";
    } else {
        $confirm_password = trim($_POST["password-confirm"]);
        if (empty($signup_err) && ($password != $confirm_password)) {
            $signup_err = "Adgangskoderne matcher ikke.";
        }
    }

    // Valider accept af vilkår
    if (!isset($_POST["terms"]) && empty($signup_err)) {
        $signup_err = "Du skal acceptere vilkår og betingelser.";
    }

    // Tjek for fejl inden vi indsætter i databasen
    if (empty($signup_err)) {

        // Forbered INSERT statement
        $sql = "INSERT INTO users (firstname, lastname, email, password) VALUES (?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variabler til prepared statement
            $stmt->bind_param("ssss", $param_firstname, $param_lastname, $param_email, $param_password);

            // Set parametre
            $param_firstname = trim($_POST["first-name"]);
            $param_lastname = trim($_POST["last-name"]);
            $param_email = $email;
            // Hash password - dette er den vigtige del for sikkerhed
            $param_password = password_hash($password, PASSWORD_DEFAULT);

            // Forsøg at eksekvere prepared statement
            if ($stmt->execute()) {
                // Get the new user ID
                $new_user_id = $stmt->insert_id;

                // Registrering fuldført og auto-login
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $new_user_id;
                $_SESSION["email"] = $email;

                // Set cookie for session persistence if remember me is checked
                if (isset($_POST["remember-signup"])) {
                    // Set cookies to last for 30 days
                    setcookie("user_login", $email, time() + (86400 * 30), "/");
                    setcookie("user_id", $new_user_id, time() + (86400 * 30), "/");
                }

                // Redirect to dashboard
                header("location: ../dashboard/");
                exit;
            } else {
                $signup_err = "Noget gik galt. Prøv igen senere.";
            }

            // Luk statement
            $stmt->close();
        }
    }
}


include("../components/header.php");
?>

<head>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }

        .bg-pattern {
            background-color: #f8fafc;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%234ade80' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .form-container {
            backdrop-filter: blur(3px);
        }
    </style>
</head>

<body class="bg-pattern">
    <!-- Navigation -->
    <nav class="bg-white shadow-md w-full z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="<?= $base ?>" class="flex-shrink-0 flex items-center">
                        <i class="fas fa-seedling text-secondary text-2xl"></i>
                        <span class="ml-2 text-xl font-semibold text-secondary">Smart Plant</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-lg form-container border border-gray-100">

            <?php if ($signup_success): ?>
                <!-- Registrering fuldført besked -->
                <div class="bg-green-100 text-green-700 p-4 rounded-md mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <p>Registrering fuldført! Du kan nu logge ind med din konto.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($login_err)): ?>
                <!-- Login fejl besked -->
                <div class="bg-red-100 text-red-700 p-4 rounded-md mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <p><?php echo $login_err; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($signup_err)): ?>
                <!-- Registrerings fejl besked -->
                <div class="bg-red-100 text-red-700 p-4 rounded-md mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <p><?php echo $signup_err; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Header with Plant Icon -->
            <div class="text-center mb-6">
                <div class="inline-block rounded-full p-5 mb-4">
                    <i class="fas fa-leaf text-secondary text-4xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900">Velkommen</h2>
                <p class="mt-2 text-sm text-gray-600">Log ind på din konto eller opret en ny</p>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-6">
                <button id="login-tab"
                    class="py-2 px-4 border-b-2 border-secondary text-secondary font-medium flex-1 flex justify-center items-center">
                    <i class="fas fa-sign-in-alt mr-2"></i> Log ind
                </button>
                <button id="signup-tab"
                    class="py-2 px-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium flex-1 flex justify-center items-center">
                    <i class="fas fa-user-plus mr-2"></i> Tilmeld
                </button>
            </div>

            <!-- Login Form -->
            <div id="login-form" class="space-y-6">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="space-y-4">
                        <div>
                            <label for="email-login" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input id="email-login" name="email" type="email" autocomplete="email" required
                                    class="appearance-none rounded-md relative block w-full pl-10 pr-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm <?php echo (!empty($email_err)) ? 'border-red-500' : ''; ?>"
                                    placeholder="Din email" value="<?php echo $email; ?>">
                            </div>
                        </div>
                        <div>
                            <label for="password-login"
                                class="block text-sm font-medium text-gray-700 mb-1">Adgangskode</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input id="password-login" name="password" type="password"
                                    autocomplete="current-password" required
                                    class="appearance-none rounded-md relative block w-full pl-10 pr-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>"
                                    placeholder="Din adgangskode">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-6">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox"
                                class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                            <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                                Husk mig
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="#" class="font-medium text-secondary hover:text-primary">
                                Glemt adgangskode?
                            </a>
                        </div>
                    </div>

                    <div class="mt-8">
                        <button type="submit" name="login" value="login"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-secondary hover:bg-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition duration-300">
                            Log ind
                        </button>
                    </div>
                </form>

                <div class="text-center">
                    <div class="text-sm">
                        <span class="text-gray-500">Har du ikke en konto?</span>
                        <button id="switch-to-signup" class="ml-1 font-medium text-secondary hover:text-primary">
                            Tilmeld dig
                        </button>
                    </div>
                </div>
            </div>

            <!-- Signup Form (initially hidden) -->
            <div id="signup-form" class="space-y-6 hidden">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="first-name"
                                    class="block text-sm font-medium text-gray-700 mb-1">Fornavn</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                    <input id="first-name" name="first-name" type="text" required
                                        class="appearance-none rounded-md relative block w-full pl-10 pr-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                        placeholder="Fornavn">
                                </div>
                            </div>
                            <div>
                                <label for="last-name"
                                    class="block text-sm font-medium text-gray-700 mb-1">Efternavn</label>
                                <input id="last-name" name="last-name" type="text" required
                                    class="appearance-none rounded-md relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                    placeholder="Efternavn">
                            </div>
                        </div>
                        <div>
                            <label for="email-signup" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input id="email-signup" name="email" type="email" autocomplete="email" required
                                    class="appearance-none rounded-md relative block w-full pl-10 pr-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                                    placeholder="Din email">
                            </div>
                        </div>
                        <div>
                            <label for="password-signup"
                                class="block text-sm font-medium text-gray-700 mb-1">Adgangskode</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input id="password-signup" name="password" type="password" autocomplete="new-password"
                                    required
                                    class="appearance-none rounded-md relative block w-full pl-10 pr-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                                    placeholder="Vælg adgangskode">
                            </div>
                        </div>
                        <div>
                            <label for="password-confirm" class="block text-sm font-medium text-gray-700 mb-1">Bekræft
                                adgangskode</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input id="password-confirm" name="password-confirm" type="password"
                                    autocomplete="new-password" required
                                    class="appearance-none rounded-md relative block w-full pl-10 pr-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                                    placeholder="Bekræft adgangskode">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center mt-6">
                        <input id="terms" name="terms" type="checkbox" required
                            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="terms" class="ml-2 block text-sm text-gray-900">
                            Jeg accepterer <a href="#" class="text-secondary hover:text-primary">vilkår og
                                betingelser</a>
                        </label>
                    </div>

                    <div class="flex items-center mt-2">
                        <input id="remember-signup" name="remember-signup" type="checkbox"
                            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="remember-signup" class="ml-2 block text-sm text-gray-900">
                            Husk mig
                        </label>
                    </div>

                    <div class="mt-8">
                        <button type="submit" name="signup" value="signup"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-secondary hover:bg-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition duration-300">
                            Opret konto
                        </button>
                    </div>
                </form>

                <div class="text-center">
                    <div class="text-sm">
                        <span class="text-gray-500">Har du allerede en konto?</span>
                        <button id="switch-to-login" class="ml-1 font-medium text-secondary hover:text-primary">
                            Log ind
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-secondary text-white py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-center space-x-2 text-center">
                <p class="text-white">&copy; 2025 Smart Plant.</p>
                <i class="fas fa-seedling text-green-200"></i>
            </div>
        </div>
    </footer>

    <script>
        // Login/Signup tabs toggle
        const loginTab = document.getElementById('login-tab');
        const signupTab = document.getElementById('signup-tab');
        const loginForm = document.getElementById('login-form');
        const signupForm = document.getElementById('signup-form');
        const switchToSignup = document.getElementById('switch-to-signup');
        const switchToLogin = document.getElementById('switch-to-login');

        function showLoginForm() {
            loginTab.classList.add('border-secondary', 'text-secondary');
            loginTab.classList.remove('border-transparent', 'text-gray-500');
            signupTab.classList.remove('border-secondary', 'text-secondary');
            signupTab.classList.add('border-transparent', 'text-gray-500');
            loginForm.classList.remove('hidden');
            signupForm.classList.add('hidden');
        }

        function showSignupForm() {
            signupTab.classList.add('border-secondary', 'text-secondary');
            signupTab.classList.remove('border-transparent', 'text-gray-500');
            loginTab.classList.remove('border-secondary', 'text-secondary');
            loginTab.classList.add('border-transparent', 'text-gray-500');
            signupForm.classList.remove('hidden');
            loginForm.classList.add('hidden');
        }

        loginTab.addEventListener('click', showLoginForm);
        signupTab.addEventListener('click', showSignupForm);
        switchToSignup.addEventListener('click', showSignupForm);
        switchToLogin.addEventListener('click', showLoginForm);

        <?php if (!empty($signup_err) || $signup_success): ?>
            // Vis signup-form hvis der er fejl i signup eller hvis signup er succesfuldt
            document.addEventListener('DOMContentLoaded', showSignupForm);
        <?php endif; ?>
    </script>
</body>