<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get BAB parameter
$bab = isset($_GET['bab']) ? (int)$_GET['bab'] : 1;

try {
    // Get lesson content
    $stmt = $pdo->prepare("
        SELECT * FROM lessons 
        WHERE bab = ? 
        ORDER BY subbab ASC
    ");
    $stmt->execute([$bab]);
    $lessons = $stmt->fetchAll();

    // Get user progress for this BAB
    $stmt = $pdo->prepare("
        SELECT l.id, COALESCE(p.phase_completed, 0) as completed
        FROM lessons l
        LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ?
        WHERE l.bab = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $bab]);
    $progress = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get comments for this BAB
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as user_name
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.bab = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$bab]);
    $comments = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log($e->getMessage());
    $error = "Terjadi kesalahan dalam memuat data";
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO comments (bab, user_id, comment_text) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$bab, $_SESSION['user_id'], $comment]);
        header("Location: lesson.php?bab=" . $bab);
        exit();
    } catch(PDOException $e) {
        error_log($e->getMessage());
        $error = "Gagal menambahkan komentar";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materi Pembelajaran - DesignHIve</title>
    
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
                        <a href="../dashboard.php">
                            <span class="font-poppins font-bold text-2xl text-primary">Design<span class="text-secondary">HIve</span></span>
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <nav class="hidden md:flex space-x-10">
                        <a href="../dashboard.php" class="text-gray-500 hover:text-gray-900">
                            Dashboard
                        </a>
                        <a href="../forum.php" class="text-gray-500 hover:text-gray-900">
                            Forum
                        </a>
                        <a href="../quiz.php" class="text-gray-500 hover:text-gray-900">
                            Kuis
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Chapter Navigation -->
        <div class="px-4 sm:px-0 mb-8">
            <div class="flex space-x-4">
                <?php for($i = 1; $i <= 3; $i++): ?>
                    <a href="?bab=<?php echo $i; ?>" 
                       class="<?php echo $bab === $i ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> px-4 py-2 rounded-md text-sm font-medium shadow-sm">
                        BAB <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>

        <div class="md:flex md:space-x-8">
            <!-- Main Lesson Content -->
            <div class="md:w-2/3">
                <div class="bg-white shadow rounded-lg" data-aos="fade-up">
                    <div class="px-4 py-5 sm:p-6">
                        <?php foreach($lessons as $lesson): ?>
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 font-poppins mb-4">
                                    <?php echo htmlspecialchars($lesson['title']); ?>
                                </h3>
                                
                                <div class="prose max-w-none">
                                    <?php echo $lesson['content']; ?>
                                </div>

                                <?php if(isset($lesson['media_url'])): ?>
                                    <div class="mt-4">
                                        <?php if(strpos($lesson['media_url'], '.mp4') !== false): ?>
                                            <video controls class="w-full rounded-lg shadow">
                                                <source src="<?php echo htmlspecialchars($lesson['media_url']); ?>" type="video/mp4">
                                                Browser Anda tidak mendukung pemutaran video.
                                            </video>
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($lesson['media_url']); ?>" 
                                                 alt="Ilustrasi materi" 
                                                 class="w-full rounded-lg shadow">
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-4">
                                    <button onclick="markAsComplete(<?php echo $lesson['id']; ?>)"
                                            class="<?php echo $progress[$lesson['id']] ? 'bg-green-500' : 'bg-primary'; ?> text-white px-4 py-2 rounded-md text-sm font-medium hover:opacity-90">
                                        <?php echo $progress[$lesson['id']] ? 'Selesai ✓' : 'Tandai Selesai'; ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar: Progress & Comments -->
            <div class="md:w-1/3 mt-8 md:mt-0">
                <!-- Progress Card -->
                <div class="bg-white shadow rounded-lg mb-6" data-aos="fade-left">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 font-poppins mb-4">
                            Progress BAB <?php echo $bab; ?>
                        </h3>
                        
                        <?php
                        $total = count($progress);
                        $completed = array_sum($progress);
                        $percentage = $total > 0 ? ($completed / $total) * 100 : 0;
                        ?>
                        
                        <div class="relative pt-1">
                            <div class="flex mb-2 items-center justify-between">
                                <div>
                                    <span class="text-xs font-semibold inline-block text-primary">
                                        Progress
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-semibold inline-block text-primary">
                                        <?php echo number_format($percentage, 0); ?>%
                                    </span>
                                </div>
                            </div>
                            <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-100">
                                <div style="width:<?php echo $percentage; ?>%" 
                                     class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-primary">
                                </div>
                            </div>
                        </div>

                        <div class="text-sm text-gray-600">
                            <?php echo $completed; ?> dari <?php echo $total; ?> sub-bab selesai
                        </div>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="bg-white shadow rounded-lg" data-aos="fade-left" data-aos-delay="100">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 font-poppins mb-4">
                            Diskusi
                        </h3>

                        <!-- Comment Form -->
                        <form action="" method="POST" class="mb-6">
                            <div>
                                <label for="comment" class="sr-only">Tambah komentar</label>
                                <textarea id="comment" name="comment" rows="3" 
                                    class="shadow-sm block w-full focus:ring-primary focus:border-primary sm:text-sm border border-gray-300 rounded-md"
                                    placeholder="Tulis komentar Anda..."></textarea>
                            </div>
                            <div class="mt-3">
                                <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                    Kirim Komentar
                                </button>
                            </div>
                        </form>

                        <!-- Comments List -->
                        <div class="space-y-4">
                            <?php foreach($comments as $comment): ?>
                                <div class="flex space-x-3">
                                    <div class="flex-shrink-0">
                                        <img class="h-10 w-10 rounded-full" 
                                             src="https://ui-avatars.com/api/?name=<?php echo urlencode($comment['user_name']); ?>&background=0066CC&color=fff" 
                                             alt="">
                                    </div>
                                    <div class="flex-1 bg-gray-50 rounded-lg px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($comment['user_name']); ?>
                                        </div>
                                        <div class="mt-1 text-sm text-gray-700">
                                            <?php echo htmlspecialchars($comment['comment_text']); ?>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500">
                                            <?php echo date('d M Y H:i', strtotime($comment['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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

        // Function to mark lesson as complete
        function markAsComplete(lessonId) {
            fetch('../api/mark_complete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    lesson_id: lessonId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Gagal menandai materi sebagai selesai');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            });
        }
    </script>
</body>
</html>
