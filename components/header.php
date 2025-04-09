<?php
$base = "http://localhost/smartplant/";
?>
<!DOCTYPE html>
<html lang="da">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Plant</title>
    <link rel="shortcut icon" href="<?= $base ?>assets/icons/favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4ade80',
                        secondary: '#059669',
                        dark: '#1f2937',
                    },
                    fontFamily: {
                        montserrat: ['Montserrat', 'sans-serif'],
                    },
                }
            }
        }
        lucide.createIcons();
    </script>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
        }

        /* Add this to the style section in your head tag */

        /* Custom scrollbar styling */
        .custom-scrollbar {
            scrollbar-width: thin;
            /* Firefox */
            scrollbar-color: rgba(156, 163, 175, 0.5) rgba(229, 231, 235, 0.5);
            /* Firefox: thumb track */
        }

        /* WebKit browsers (Chrome, Safari, newer versions of Opera) */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(229, 231, 235, 0.5);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.5);
            border-radius: 10px;
            border: 2px solid transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: rgba(107, 114, 128, 0.7);
        }
    </style>
</head>