<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $nis = filter_input(INPUT_POST, 'nis', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'student'; // Default role

    // Validate input
    if (strlen($password) < 6) {
        $error = "Password harus minimal 6 karakter";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok";
    } else {
        try {
            // Check if NIS already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE nis = ?");
            $stmt->execute([$nis]);
            
            if ($stmt->fetch()) {
                $error = "NIS sudah terdaftar";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, nis, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $nis, $hashed_password, $role]);
                
                $success = "Pendaftaran berhasil! Silakan login.";
                header("refresh:2;url=login.php"); // Redirect to login after 2 seconds
            }
        } catch(PDOException $e) {
            $error = "Terjadi kesalahan. Silakan coba lagi nanti.";
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - DesignHIve</title>
    
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
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 font-poppins">
                Daftar Akun Baru
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Atau
                <a href="login.php" class="font-medium text-primary hover:text-blue-500">
                    masuk jika sudah memiliki akun
                </a>
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <form class="space-y-6" action="register.php" method="POST">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Nama Lengkap
                        </label>
                        <div class="mt-1">
                            <input id="name" name="name" type="text" required 
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                placeholder="Masukkan nama lengkap">
                        </div>
                    </div>

                    <div>
                        <label for="nis" class="block text-sm font-medium text-gray-700">
                            NIS (Nomor Induk Siswa)
                        </label>
                        <div class="mt-1">
                            <input id="nis" name="nis" type="text" required 
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                placeholder="Masukkan NIS">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" required 
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                placeholder="Minimal 6 karakter">
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                            Konfirmasi Password
                        </label>
                        <div class="mt-1">
                            <input id="confirm_password" name="confirm_password" type="password" required 
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                placeholder="Masukkan ulang password">
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Daftar
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">
                                Atau daftar dengan
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <div>
                            <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fab fa-google text-red-600"></i>
                                <span class="ml-2">Google</span>
                            </a>
                        </div>
                        <div>
                            <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fab fa-microsoft text-blue-600"></i>
                                <span class="ml-2">Microsoft</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
