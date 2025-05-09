<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Get user progress
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as completed_lessons 
        FROM progress 
        WHERE user_id = ? AND phase_completed = 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $progress = $stmt->fetch();

    // Get recent activities
    $stmt = $pdo->prepare("
        SELECT l.title, p.created_at 
        FROM progress p 
        JOIN lessons l ON p.lesson_id = l.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $activities = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log($e->getMessage());
    $error = "Terjadi kesalahan dalam memuat data";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - DesignHIve</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
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
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="font-poppins font-bold text-2xl text-primary">Design<span class="text-secondary">HIve</span></span>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="hidden md:ml-6 md:flex md:items-center md:space-x-4">
                        <a href="dashboard.php" class="text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="forum.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Forum</a>
                        <a href="quiz.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Kuis</a>
                        <a href="assignment.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Tugas</a>
                    </div>
                    <div class="ml-4 flex items-center">
                        <div class="ml-3 relative">
                            <div>
                                <button type="button" class="flex items-center max-w-xs bg-white rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="user-menu-button">
                                    <span class="sr-only">Open user menu</span>
                                    <img class="h-8 w-8 rounded-full" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=0066CC&color=fff" alt="">
                                </button>
                            </div>
                        </div>
                        <a href="logout.php" class="ml-4 text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white rounded-lg shadow px-5 py-6 sm:px-6" data-aos="fade-up">
                <h2 class="text-2xl font-bold text-gray-900 font-poppins mb-4">
                    Selamat datang, <?php echo htmlspecialchars($user['name']); ?>! 👋
                </h2>
                <p class="text-gray-600">
                    Lanjutkan perjalanan belajar desain grafis Anda.
                </p>
            </div>
        </div>

        <!-- Progress & Quick Actions -->
        <div class="px-4 py-6 sm:px-0">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Progress Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg" data-aos="fade-up" data-aos-delay="100">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-chart-line text-primary text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Progress Pembelajaran
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            <?php echo $progress['completed_lessons']; ?> Materi
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <a href="Lesson/lesson.php" class="font-medium text-primary hover:text-blue-500">
                                Lihat detail
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quiz Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg" data-aos="fade-up" data-aos-delay="200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-tasks text-primary text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Kuis Tersedia
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            5 Kuis
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <a href="quiz.php" class="font-medium text-primary hover:text-blue-500">
                                Mulai kuis
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Assignment Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg" data-aos="fade-up" data-aos-delay="300">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-file-alt text-primary text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Tugas Menunggu
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            2 Tugas
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <a href="assignment.php" class="font-medium text-primary hover:text-blue-500">
                                Lihat tugas
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Forum Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg" data-aos="fade-up" data-aos-delay="400">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-comments text-primary text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Diskusi Forum
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            10 Diskusi Baru
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <a href="forum.php" class="font-medium text-primary hover:text-blue-500">
                                Gabung diskusi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Learning Materials -->
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white shadow rounded-lg" data-aos="fade-up">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 font-poppins mb-4">
                        Materi Pembelajaran
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- BAB 1 -->
                        <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary">
                            <div class="flex-shrink-0">
                                <i class="fas fa-book text-primary text-2xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <a href="Lesson/lesson.php?bab=1" class="focus:outline-none">
                                    <span class="absolute inset-0" aria-hidden="true"></span>
                                    <p class="text-sm font-medium text-gray-900">BAB 1</p>
                                    <p class="text-sm text-gray-500 truncate">Pengenalan Desain Grafis</p>
                                </a>
                            </div>
                        </div>

                        <!-- BAB 2 -->
                        <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary">
                            <div class="flex-shrink-0">
                                <i class="fas fa-palette text-primary text-2xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <a href="Lesson/lesson.php?bab=2" class="focus:outline-none">
                                    <span class="absolute inset-0" aria-hidden="true"></span>
                                    <p class="text-sm font-medium text-gray-900">BAB 2</p>
                                    <p class="text-sm text-gray-500 truncate">Teori Warna</p>
                                </a>
                            </div>
                        </div>

                        <!-- BAB 3 -->
                        <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary">
                            <div class="flex-shrink-0">
                                <i class="fas fa-vector-square text-primary text-2xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <a href="Lesson/lesson.php?bab=3" class="focus:outline-none">
                                    <span class="absolute inset-0" aria-hidden="true"></span>
                                    <p class="text-sm font-medium text-gray-900">BAB 3</p>
                                    <p class="text-sm text-gray-500 truncate">Tipografi</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white shadow rounded-lg" data-aos="fade-up">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 font-poppins mb-4">
                        Aktivitas Terakhir
                    </h3>
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            <?php foreach ($activities as $activity): ?>
                            <li>
                                <div class="relative pb-8">
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-primary flex items-center justify-center ring-8 ring-white">
                                                <i class="fas fa-check text-white"></i>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-sm text-gray-500">
                                                    Menyelesaikan materi <span class="font-medium text-gray-900"><?php echo htmlspecialchars($activity['title']); ?></span>
                                                </p>
                                            </div>
                                            <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                <time datetime="<?php echo $activity['created_at']; ?>">
                                                    <?php echo date('d M Y', strtotime($activity['created_at'])); ?>
                                                </time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
        });
    </script>
</body>
</html>
