<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
try {
    $stmt = $pdo->prepare("SELECT name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    error_log($e->getMessage());
}

// Handle new thread creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_thread') {
    $bab = filter_input(INPUT_POST, 'bab', FILTER_SANITIZE_NUMBER_INT);
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO forum (bab, thread_title, thread_content, created_by, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$bab, $title, $content, $_SESSION['user_id']]);
        header("Location: forum.php?bab=" . $bab);
        exit();
    } catch(PDOException $e) {
        error_log($e->getMessage());
        $error = "Gagal membuat thread baru";
    }
}

// Handle search
$search = isset($_GET['search']) ? filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) : '';
$bab = isset($_GET['bab']) ? filter_input(INPUT_GET, 'bab', FILTER_SANITIZE_NUMBER_INT) : null;

try {
    // Prepare base query
    $query = "
        SELECT f.*, u.name as creator_name,
               (SELECT COUNT(*) FROM forum_comments WHERE forum_id = f.id) as comment_count
        FROM forum f
        JOIN users u ON f.created_by = u.id
    ";
    $params = [];

    // Add search condition if search term exists
    if ($search) {
        $query .= " WHERE (f.thread_title LIKE ? OR f.thread_content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Add BAB filter if specified
    if ($bab) {
        $query .= $search ? " AND" : " WHERE";
        $query .= " f.bab = ?";
        $params[] = $bab;
    }

    $query .= " ORDER BY f.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $threads = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log($e->getMessage());
    $error = "Terjadi kesalahan dalam memuat data forum";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Diskusi - DesignHIve</title>
    
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
                        <a href="dashboard.php">
                            <span class="font-poppins font-bold text-2xl text-primary">Design<span class="text-secondary">HIve</span></span>
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <nav class="hidden md:flex space-x-10">
                        <a href="dashboard.php" class="text-gray-500 hover:text-gray-900">
                            Dashboard
                        </a>
                        <a href="forum.php" class="text-gray-900">
                            Forum
                        </a>
                        <a href="quiz.php" class="text-gray-500 hover:text-gray-900">
                            Kuis
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="px-4 sm:px-0 mb-8">
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 font-poppins sm:text-3xl sm:truncate">
                        Forum Diskusi
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <button type="button" onclick="showNewThreadModal()" 
                            class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Thread Baru
                    </button>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="px-4 sm:px-0 mb-6">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="md:flex md:items-center md:justify-between">
                    <!-- Search -->
                    <div class="flex-1 min-w-0">
                        <form action="" method="GET" class="max-w-lg">
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                       class="focus:ring-primary focus:border-primary block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                                       placeholder="Cari diskusi...">
                            </div>
                        </form>
                    </div>

                    <!-- BAB Filter -->
                    <div class="mt-4 md:mt-0 md:ml-4">
                        <select onchange="window.location.href='forum.php?bab='+this.value" 
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                            <option value="">Semua BAB</option>
                            <?php for($i = 1; $i <= 3; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $bab == $i ? 'selected' : ''; ?>>
                                    BAB <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thread List -->
        <div class="px-4 sm:px-0">
            <div class="bg-white shadow rounded-lg" data-aos="fade-up">
                <div class="px-4 py-5 sm:p-6">
                    <?php if (empty($threads)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-comments text-gray-400 text-5xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada diskusi</h3>
                            <p class="text-gray-500">Mulai diskusi pertama dengan mengklik tombol 'Thread Baru'</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($threads as $thread): ?>
                                <div class="bg-white border rounded-lg p-6 hover:border-primary transition-colors duration-200">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start space-x-3">
                                            <img class="h-10 w-10 rounded-full" 
                                                 src="https://ui-avatars.com/api/?name=<?php echo urlencode($thread['creator_name']); ?>&background=0066CC&color=fff" 
                                                 alt="">
                                            <div>
                                                <a href="thread.php?id=<?php echo $thread['id']; ?>" class="text-lg font-medium text-gray-900 hover:text-primary">
                                                    <?php echo htmlspecialchars($thread['thread_title']); ?>
                                                </a>
                                                <div class="mt-1 flex items-center space-x-2 text-sm text-gray-500">
                                                    <span><?php echo htmlspecialchars($thread['creator_name']); ?></span>
                                                    <span>&bull;</span>
                                                    <span>BAB <?php echo $thread['bab']; ?></span>
                                                    <span>&bull;</span>
                                                    <span><?php echo date('d M Y', strtotime($thread['created_at'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-comment-alt mr-1"></i>
                                                <?php echo $thread['comment_count']; ?>
                                            </span>
                                            <?php if ($user['role'] === 'teacher'): ?>
                                                <button onclick="deleteThread(<?php echo $thread['id']; ?>)" 
                                                        class="text-red-600 hover:text-red-800">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-gray-600 line-clamp-2">
                                        <?php echo htmlspecialchars($thread['thread_content']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- New Thread Modal -->
    <div id="newThreadModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button type="button" onclick="hideNewThreadModal()" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="create_thread">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 font-poppins" id="modal-title">
                            Thread Baru
                        </h3>
                        <div class="mt-6 space-y-6">
                            <div>
                                <label for="bab" class="block text-sm font-medium text-gray-700">
                                    BAB
                                </label>
                                <select id="bab" name="bab" required
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                                    <?php for($i = 1; $i <= 3; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $bab == $i ? 'selected' : ''; ?>>
                                            BAB <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">
                                    Judul
                                </label>
                                <input type="text" name="title" id="title" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                            </div>
                            <div>
                                <label for="content" class="block text-sm font-medium text-gray-700">
                                    Konten
                                </label>
                                <textarea id="content" name="content" rows="4" required
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6">
                        <button type="submit" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:text-sm">
                            Buat Thread
                        </button>
                    </div>
                </form>
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

        // Modal functions
        function showNewThreadModal() {
            document.getElementById('newThreadModal').classList.remove('hidden');
        }

        function hideNewThreadModal() {
            document.getElementById('newThreadModal').classList.add('hidden');
        }

        // Delete thread function (for teachers)
        function deleteThread(threadId) {
            if (confirm('Apakah Anda yakin ingin menghapus thread ini?')) {
                fetch('api/delete_thread.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        thread_id: threadId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Gagal menghapus thread');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan');
                });
            }
        }
    </script>
</body>
</html>
