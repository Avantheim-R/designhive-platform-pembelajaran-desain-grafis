<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

try {
    // Get teacher data
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher = $stmt->fetch();

    // Get pending assignments count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as pending_assignments 
        FROM assignments 
        WHERE grade IS NULL
    ");
    $stmt->execute();
    $pending = $stmt->fetch();

    // Get recent submissions
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as student_name, l.title as lesson_title
        FROM assignments a
        JOIN users u ON a.user_id = u.id
        JOIN lessons l ON a.lesson_id = l.id
        WHERE a.grade IS NULL
        ORDER BY a.submitted_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_submissions = $stmt->fetchAll();

    // Get student progress statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT user_id) as total_students,
            AVG(CASE WHEN phase_completed = 1 THEN 1 ELSE 0 END) * 100 as completion_rate
        FROM progress
    ");
    $stmt->execute();
    $stats = $stmt->fetch();

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
    <title>Dashboard Guru - DesignHIve</title>
    
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
                        <a href="teacher_dashboard.php" class="text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="manage_content.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Kelola Konten</a>
                        <a href="grade_assignments.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Penilaian</a>
                        <a href="analytics.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Analitik</a>
                    </div>
                    <div class="ml-4 flex items-center">
                        <div class="ml-3 relative">
                            <div>
                                <button type="button" class="flex items-center max-w-xs bg-white rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="user-menu-button">
                                    <span class="sr-only">Open user menu</span>
                                    <img class="h-8 w-8 rounded-full" src="https://ui-avatars.com/api/?name=<?php echo urlencode($teacher['name']); ?>&background=0066CC&color=fff" alt="">
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
                    Selamat datang, <?php echo htmlspecialchars($teacher['name']); ?>! 👋
                </h2>
                <p class="text-gray-600">
                    Panel kontrol untuk mengelola pembelajaran dan memantau progres siswa.
                </p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="px-4 py-6 sm:px-0">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Pending Assignments -->
                <div class="bg-white overflow-hidden shadow rounded-lg" data-aos="fade-up" data-aos-delay="100">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-tasks text-primary text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Tugas Menunggu Penilaian
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            <?php echo $pending['pending_assignments']; ?> Tugas
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <a href="grade_assignments.php" class="font-medium text-primary hover:text-blue-500">
                                Nilai sekarang
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Total Students -->
                <div class="bg-white overflow-hidden shadow rounded-lg" data-aos="fade-up" data-aos-delay="200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-users text-primary text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Total Siswa
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            <?php echo $stats['total_students']; ?> Siswa
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <a href="student_list.php" class="font-medium text-primary hover:text-blue-500">
                                Lihat daftar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Completion Rate -->
                <div class="bg-white overflow-hidden shadow rounded-lg" data-aos="fade-up" data-aos-delay="300">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-chart-line text-primary text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Rata-rata Penyelesaian
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            <?php echo number_format($stats['completion_rate'], 1); ?>%
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <a href="analytics.php" class="font-medium text-primary hover:text-blue-500">
                                Lihat analitik
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Forum Activity -->
                <div class="bg-white overflow-hidden shadow rounded-lg" data-aos="fade-up" data-aos-delay="400">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-comments text-primary text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Aktivitas Forum
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            15 Diskusi Baru
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <a href="forum.php" class="font-medium text-primary hover:text-blue-500">
                                Moderasi forum
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Submissions -->
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white shadow rounded-lg" data-aos="fade-up">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 font-poppins mb-4">
                        Pengumpulan Tugas Terbaru
                    </h3>
                    <div class="flex flex-col">
                        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Siswa
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Materi
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Tanggal
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th scope="col" class="relative px-6 py-3">
                                                    <span class="sr-only">Edit</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($recent_submissions as $submission): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10">
                                                            <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name=<?php echo urlencode($submission['student_name']); ?>&background=0066CC&color=fff" alt="">
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900">
                                                                <?php echo htmlspecialchars($submission['student_name']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($submission['lesson_title']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo date('d M Y', strtotime($submission['submitted_at'])); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Menunggu Penilaian
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <a href="grade_assignment.php?id=<?php echo $submission['id']; ?>" class="text-primary hover:text-blue-700">Nilai</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
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
