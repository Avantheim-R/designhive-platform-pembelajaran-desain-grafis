<?php
// Initialize error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Try to include configuration files
$dbConnected = false;
try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    require_once 'includes/session.php';
    require_once 'includes/utils.php';
    $dbConnected = isset($pdo);
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Check if user is already logged in and database is connected
if ($dbConnected && isLoggedIn()) {
    // Redirect based on user role
    if (isTeacher()) {
        header("Location: public/teacher_dashboard.php");
    } else {
        header("Location: public/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to DesignHive</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0066CC',
                        secondary: '#FFD700',
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                        inter: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .font-poppins {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h1 class="text-center text-4xl font-extrabold text-gray-900 font-poppins mb-2">
                Design<span class="text-primary">Hive</span>
            </h1>
            <h2 class="text-center text-2xl font-bold text-gray-900">
                Platform Pembelajaran Desain Grafis
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Belajar desain grafis secara terstruktur dan interaktif
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <?php if (!$dbConnected): ?>
                    <div class="rounded-md bg-yellow-50 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Maintenance Mode
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>
                                        Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <div>
                            <a href="public/login.php" 
                               class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Masuk
                            </a>
                        </div>
                        <div>
                            <a href="public/register.php" 
                               class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Daftar
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Features Section -->
        <div class="mt-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Feature 1 -->
                <div class="bg-white overflow-hidden shadow rounded-lg" data-aos="fade-up">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-book text-primary text-3xl"></i>
                            </div>
                            <div class="ml-5">
                                <h3 class="text-lg font-medium text-gray-900">
                                    Materi Terstruktur
                                </h3>
                                <p class="mt-2 text-sm text-gray-500">
                                    Pembelajaran bertahap dengan materi yang terorganisir
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white overflow-hidden shadow rounded-lg" data-aos="fade-up" data-aos-delay="100">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-tasks text-primary text-3xl"></i>
                            </div>
                            <div class="ml-5">
                                <h3 class="text-lg font-medium text-gray-900">
                                    Kuis Interaktif
                                </h3>
                                <p class="mt-2 text-sm text-gray-500">
                                    Latihan dan evaluasi dengan feedback langsung
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white overflow-hidden shadow rounded-lg" data-aos="fade-up" data-aos-delay="200">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-comments text-primary text-3xl"></i>
                            </div>
                            <div class="ml-5">
                                <h3 class="text-lg font-medium text-gray-900">
                                    Forum Diskusi
                                </h3>
                                <p class="mt-2 text-sm text-gray-500">
                                    Diskusi dan tanya jawab dengan guru dan sesama siswa
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white mt-12">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 md:flex md:items-center md:justify-between lg:px-8">
            <div class="flex justify-center space-x-6 md:order-2">
                <span class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">GitHub</span>
                    <i class="fab fa-github text-xl"></i>
                </span>
            </div>
            <div class="mt-8 md:mt-0 md:order-1">
                <p class="text-center text-base text-gray-400">
                    &copy; 2024 DesignHive. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
