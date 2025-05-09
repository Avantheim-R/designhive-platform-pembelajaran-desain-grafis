<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data and role
try {
    $stmt = $pdo->prepare("SELECT name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    error_log($e->getMessage());
    $error = "Terjadi kesalahan dalam memuat data pengguna";
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['assignment'])) {
    $lesson_id = filter_input(INPUT_POST, 'lesson_id', FILTER_SANITIZE_NUMBER_INT);
    $file = $_FILES['assignment'];
    
    // Validate file
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        $error = "Tipe file tidak didukung. Harap upload file PDF atau DOC/DOCX.";
    } elseif ($file['size'] > $max_size) {
        $error = "Ukuran file terlalu besar. Maksimal 5MB.";
    } else {
        try {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/assignments/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $filename = uniqid() . '_' . $file['name'];
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Save to database
                $stmt = $pdo->prepare("
                    INSERT INTO assignments (lesson_id, user_id, file_path, submitted_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$lesson_id, $_SESSION['user_id'], $filepath]);
                $success = "File berhasil diupload!";
            } else {
                $error = "Gagal mengupload file.";
            }
        } catch(PDOException $e) {
            error_log($e->getMessage());
            $error = "Terjadi kesalahan dalam menyimpan data tugas";
        }
    }
}

// Get assignments based on user role
try {
    if ($user['role'] === 'student') {
        // Get student's assignments
        $stmt = $pdo->prepare("
            SELECT a.*, l.title as lesson_title, l.bab
            FROM assignments a
            JOIN lessons l ON a.lesson_id = l.id
            WHERE a.user_id = ?
            ORDER BY a.submitted_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $assignments = $stmt->fetchAll();

        // Get available lessons for assignment submission
        $stmt = $pdo->prepare("
            SELECT id, title, bab 
            FROM lessons 
            ORDER BY bab ASC, id ASC
        ");
        $stmt->execute();
        $lessons = $stmt->fetchAll();
    } else {
        // Get all assignments for teacher
        $stmt = $pdo->prepare("
            SELECT a.*, l.title as lesson_title, l.bab, u.name as student_name
            FROM assignments a
            JOIN lessons l ON a.lesson_id = l.id
            JOIN users u ON a.user_id = u.id
            ORDER BY a.submitted_at DESC
        ");
        $stmt->execute();
        $assignments = $stmt->fetchAll();
    }
} catch(PDOException $e) {
    error_log($e->getMessage());
    $error = "Terjadi kesalahan dalam memuat data tugas";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas - DesignHIve</title>
    
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
                        <a href="forum.php" class="text-gray-500 hover:text-gray-900">
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
            <h2 class="text-2xl font-bold text-gray-900 font-poppins">
                <?php echo $user['role'] === 'student' ? 'Tugas Saya' : 'Penilaian Tugas'; ?>
            </h2>
        </div>

        <?php if (isset($error)): ?>
            <div class="px-4 sm:px-0 mb-6">
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="px-4 sm:px-0 mb-6">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $success; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($user['role'] === 'student'): ?>
        <!-- Upload Assignment Section -->
        <div class="px-4 sm:px-0 mb-8">
            <div class="bg-white shadow rounded-lg" data-aos="fade-up">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 font-poppins mb-4">
                        Upload Tugas Baru
                    </h3>
                    <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div>
                            <label for="lesson_id" class="block text-sm font-medium text-gray-700">
                                Pilih Materi
                            </label>
                            <select id="lesson_id" name="lesson_id" required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                                <?php foreach($lessons as $lesson): ?>
                                    <option value="<?php echo $lesson['id']; ?>">
                                        BAB <?php echo $lesson['bab']; ?> - <?php echo htmlspecialchars($lesson['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                File Tugas (PDF/DOC/DOCX, max 5MB)
                            </label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-file-upload text-gray-400 text-3xl mb-3"></i>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="assignment" class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                            <span>Upload file</span>
                                            <input id="assignment" name="assignment" type="file" class="sr-only" required
                                                   accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                                        </label>
                                        <p class="pl-1">atau drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        PDF, DOC, atau DOCX hingga 5MB
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <button type="submit" 
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Upload Tugas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Assignments List -->
        <div class="px-4 sm:px-0">
            <div class="bg-white shadow rounded-lg" data-aos="fade-up">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 font-poppins mb-6">
                        <?php echo $user['role'] === 'student' ? 'Riwayat Tugas' : 'Daftar Tugas Siswa'; ?>
                    </h3>

                    <?php if (empty($assignments)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-file-alt text-gray-400 text-5xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada tugas</h3>
                            <?php if ($user['role'] === 'student'): ?>
                                <p class="text-gray-500">Upload tugas pertama Anda menggunakan form di atas</p>
                            <?php else: ?>
                                <p class="text-gray-500">Belum ada siswa yang mengumpulkan tugas</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col">
                            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <?php if ($user['role'] === 'teacher'): ?>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Siswa
                                                        </th>
                                                    <?php endif; ?>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Materi
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Tanggal Upload
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Status
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Nilai
                                                    </th>
                                                    <th scope="col" class="relative px-6 py-3">
                                                        <span class="sr-only">Aksi</span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($assignments as $assignment): ?>
                                                    <tr>
                                                        <?php if ($user['role'] === 'teacher'): ?>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <div class="flex items-center">
                                                                    <div class="flex-shrink-0 h-10 w-10">
                                                                        <img class="h-10 w-10 rounded-full" 
                                                                             src="https://ui-avatars.com/api/?name=<?php echo urlencode($assignment['student_name']); ?>&background=0066CC&color=fff" 
                                                                             alt="">
                                                                    </div>
                                                                    <div class="ml-4">
                                                                        <div class="text-sm font-medium text-gray-900">
                                                                            <?php echo htmlspecialchars($assignment['student_name']); ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-900">
                                                                BAB <?php echo $assignment['bab']; ?> - <?php echo htmlspecialchars($assignment['lesson_title']); ?>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-900">
                                                                <?php echo date('d M Y H:i', strtotime($assignment['submitted_at'])); ?>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $assignment['grade'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                                <?php echo $assignment['grade'] ? 'Dinilai' : 'Menunggu Penilaian'; ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            <?php echo $assignment['grade'] ? $assignment['grade'] : '-'; ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            <a href="<?php echo $assignment['file_path']; ?>" target="_blank" 
                                                               class="text-primary hover:text-blue-700 mr-4">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            <?php if ($user['role'] === 'teacher' && !$assignment['grade']): ?>
                                                                <button onclick="showGradingModal(<?php echo $assignment['id']; ?>)" 
                                                                        class="text-primary hover:text-blue-700">
                                                                    <i class="fas fa-star"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Grading Modal -->
    <?php if ($user['role'] === 'teacher'): ?>
    <div id="gradingModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button type="button" onclick="hideGradingModal()" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="gradingForm" onsubmit="submitGrade(event)">
                    <input type="hidden" id="assignment_id" name="assignment_id">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 font-poppins" id="modal-title">
                            Beri Nilai
                        </h3>
                        <div class="mt-6 space-y-6">
                            <div>
                                <label for="grade" class="block text-sm font-medium text-gray-700">
                                    Nilai (0-100)
                                </label>
                                <input type="number" name="grade" id="grade" min="0" max="100" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                            </div>
                            <div>
                                <label for="feedback" class="block text-sm font-medium text-gray-700">
                                    Feedback
                                </label>
                                <textarea id="feedback" name="feedback" rows="4"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6">
                        <button type="submit" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:text-sm">
                            Simpan Nilai
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
        });

        <?php if ($user['role'] === 'teacher'): ?>
        // Grading modal functions
        function showGradingModal(assignmentId) {
            document.getElementById('assignment_id').value = assignmentId;
            document.getElementById('gradingModal').classList.remove('hidden');
        }

        function hideGradingModal() {
            document.getElementById('gradingModal').classList.add('hidden');
            document.getElementById('gradingForm').reset();
        }

        function submitGrade(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('api/grade_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    assignment_id: formData.get('assignment_id'),
                    grade: formData.get('grade'),
                    feedback: formData.get('feedback')
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hideGradingModal();
                    location.reload();
                } else {
                    alert('Gagal menyimpan nilai');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            });
        }
        <?php endif; ?>

        // File upload preview
        const fileInput = document.getElementById('assignment');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const fileName = e.target.files[0].name;
                const label = e.target.parentElement;
                const preview = label.parentElement;
                preview.querySelector('p').textContent = fileName;
            });
        }
    </script>
</body>
</html>
