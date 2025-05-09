<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get BAB parameter for specific quizzes
$bab = isset($_GET['bab']) ? (int)$_GET['bab'] : null;

try {
    if ($bab) {
        // Get specific BAB quizzes
        $stmt = $pdo->prepare("
            SELECT * FROM quizzes 
            WHERE bab = ? 
            ORDER BY quiz_order ASC
        ");
        $stmt->execute([$bab]);
    } else {
        // Get all available quizzes
        $stmt = $pdo->prepare("
            SELECT q.*, 
                   (SELECT COUNT(*) FROM quiz_results WHERE quiz_id = q.id AND user_id = ?) as attempted,
                   (SELECT MAX(score) FROM quiz_results WHERE quiz_id = q.id AND user_id = ?) as best_score
            FROM quizzes q
            ORDER BY q.bab ASC, q.quiz_order ASC
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    }
    $quizzes = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log($e->getMessage());
    $error = "Terjadi kesalahan dalam memuat data kuis";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuis - DesignHIve</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Drag and Drop -->
    <script src="https://cdn.jsdelivr.net/npm/@shopify/draggable@1.0.0-beta.8/lib/draggable.bundle.js"></script>
    
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
        .draggable-source--is-dragging {
            opacity: 0.5;
        }
        .draggable-mirror {
            opacity: 0.9;
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
                        <a href="quiz.php" class="text-gray-900">
                            Kuis
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if (!$bab): ?>
        <!-- Quiz Overview -->
        <div class="px-4 sm:px-0">
            <div class="bg-white shadow rounded-lg" data-aos="fade-up">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-2xl font-bold text-gray-900 font-poppins mb-6">
                        Kuis Tersedia
                    </h2>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($quizzes as $quiz): ?>
                        <div class="bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        BAB <?php echo $quiz['bab']; ?> - Kuis <?php echo $quiz['quiz_order']; ?>
                                    </h3>
                                    <?php if ($quiz['attempted']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Selesai
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="text-sm text-gray-600 mb-4">
                                    <?php echo htmlspecialchars($quiz['title']); ?>
                                </p>
                                
                                <?php if ($quiz['attempted']): ?>
                                <div class="mb-4">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Nilai Terbaik:</span>
                                        <span class="font-medium text-primary"><?php echo $quiz['best_score']; ?>%</span>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-500">
                                        <?php echo $quiz['duration']; ?> menit
                                    </div>
                                    <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" 
                                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-blue-700">
                                        <?php echo $quiz['attempted'] ? 'Coba Lagi' : 'Mulai Kuis'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Quiz Interface -->
        <div id="quiz-container" class="px-4 sm:px-0">
            <div class="bg-white shadow rounded-lg" data-aos="fade-up">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 font-poppins">
                            BAB <?php echo $bab; ?> - Kuis
                        </h2>
                        <div class="text-lg font-medium text-primary" id="timer">
                            00:00
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="relative pt-1 mb-6">
                        <div class="flex mb-2 items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold inline-block text-primary">
                                    Progress
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold inline-block text-primary">
                                    <span id="current-question">1</span>/<span id="total-questions">10</span>
                                </span>
                            </div>
                        </div>
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-100">
                            <div id="progress-bar" style="width:10%" 
                                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-primary transition-all duration-500">
                            </div>
                        </div>
                    </div>

                    <!-- Question Container -->
                    <div id="question-container" class="space-y-8">
                        <!-- Multiple Choice Question Example -->
                        <div class="question-item" data-type="multiple-choice">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Apa yang dimaksud dengan prinsip kontras dalam desain grafis?
                            </h3>
                            <div class="space-y-4">
                                <label class="flex items-center p-4 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                    <input type="radio" name="q1" value="a" class="h-4 w-4 text-primary">
                                    <span class="ml-3 text-gray-700">Perbedaan yang mencolok antara elemen desain</span>
                                </label>
                                <label class="flex items-center p-4 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                    <input type="radio" name="q1" value="b" class="h-4 w-4 text-primary">
                                    <span class="ml-3 text-gray-700">Pengulangan elemen desain</span>
                                </label>
                                <label class="flex items-center p-4 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                    <input type="radio" name="q1" value="c" class="h-4 w-4 text-primary">
                                    <span class="ml-3 text-gray-700">Keseimbangan dalam layout</span>
                                </label>
                            </div>
                        </div>

                        <!-- Drag and Drop Question Example -->
                        <div class="question-item hidden" data-type="drag-drop">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Susun urutan proses desain berikut dengan benar:
                            </h3>
                            <div class="space-y-4">
                                <div class="draggable-container">
                                    <div class="draggable-source bg-white p-4 border rounded-lg cursor-move" draggable="true">
                                        Penelitian dan Analisis
                                    </div>
                                    <div class="draggable-source bg-white p-4 border rounded-lg cursor-move" draggable="true">
                                        Sketsa dan Wireframe
                                    </div>
                                    <div class="draggable-source bg-white p-4 border rounded-lg cursor-move" draggable="true">
                                        Desain Final
                                    </div>
                                </div>
                                <div class="draggable-target space-y-2 p-4 border-2 border-dashed rounded-lg min-h-[200px]">
                                    <p class="text-gray-400 text-center">Seret item ke sini</p>
                                </div>
                            </div>
                        </div>

                        <!-- Matching Question Example -->
                        <div class="question-item hidden" data-type="matching">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Cocokkan warna dengan psikologi yang tepat:
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-4">
                                    <div class="p-4 bg-red-100 rounded-lg">Merah</div>
                                    <div class="p-4 bg-blue-100 rounded-lg">Biru</div>
                                    <div class="p-4 bg-green-100 rounded-lg">Hijau</div>
                                </div>
                                <div class="space-y-4">
                                    <div class="p-4 border rounded-lg" data-match="blue">Ketenangan</div>
                                    <div class="p-4 border rounded-lg" data-match="red">Energi</div>
                                    <div class="p-4 border rounded-lg" data-match="green">Harmoni</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="mt-8 flex justify-between">
                        <button id="prev-btn" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                                disabled>
                            <i class="fas fa-chevron-left mr-2"></i>
                            Sebelumnya
                        </button>
                        <button id="next-btn" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-blue-700">
                            Selanjutnya
                            <i class="fas fa-chevron-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
        });

        <?php if ($bab): ?>
        // Quiz Interface JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const questions = document.querySelectorAll('.question-item');
            let currentQuestion = 0;
            const totalQuestions = questions.length;

            // Update progress
            function updateProgress() {
                document.getElementById('current-question').textContent = currentQuestion + 1;
                document.getElementById('total-questions').textContent = totalQuestions;
                document.getElementById('progress-bar').style.width = `${((currentQuestion + 1) / totalQuestions) * 100}%`;
            }

            // Show question
            function showQuestion(index) {
                questions.forEach((q, i) => {
                    if (i === index) {
                        q.classList.remove('hidden');
                    } else {
                        q.classList.add('hidden');
                    }
                });

                // Update button states
                document.getElementById('prev-btn').disabled = index === 0;
                document.getElementById('next-btn').textContent = index === totalQuestions - 1 ? 'Selesai' : 'Selanjutnya';

                updateProgress();
            }

            // Navigation event listeners
            document.getElementById('prev-btn').addEventListener('click', () => {
                if (currentQuestion > 0) {
                    currentQuestion--;
                    showQuestion(currentQuestion);
                }
            });

            document.getElementById('next-btn').addEventListener('click', () => {
                if (currentQuestion < totalQuestions - 1) {
                    currentQuestion++;
                    showQuestion(currentQuestion);
                } else {
                    // Submit quiz
                    submitQuiz();
                }
            });

            // Initialize Drag and Drop
            if (document.querySelector('.draggable-container')) {
                new Draggable.Sortable(document.querySelector('.draggable-container'), {
                    draggable: '.draggable-source',
                    mirror: {
                        constrainDimensions: true,
                    }
                });
            }

            // Initialize first question
            showQuestion(0);

            // Timer
            let timeLeft = <?php echo isset($quiz['duration']) ? $quiz['duration'] * 60 : 600; ?>; // Default 10 minutes
            const timerDisplay = document.getElementById('timer');

            const timer = setInterval(() => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (timeLeft === 0) {
                    clearInterval(timer);
                    submitQuiz();
                } else {
                    timeLeft--;
                }
            }, 1000);

            // Submit quiz function
            function submitQuiz() {
                // Collect answers
                const answers = {
                    multipleChoice: {},
                    dragDrop: [],
                    matching: {}
                };

                // Submit to server
                fetch('api/submit_quiz.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(answers)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `quiz_result.php?id=${data.result_id}`;
                    } else {
                        alert('Terjadi kesalahan saat mengirim jawaban');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan');
                });
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
