<?php
// user/start_exam.php
session_start();
require_once "../config.php";

// Təhlükəsizlik yoxlaması: Yalnız giriş etmiş tələbələrə icazə ver
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Verilənlər bazası əlaqəsini qur
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // PDO parametrlərini təkmilləşdir
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    header("Location: dashboard.php?error=database_connection");
    exit();
}

// İstifadəçinin inter dəyərini yoxla
$user_int = $_SESSION['inter'] ?? null;
if (!$user_int) {
    header("Location: dashboard.php?error=no_question_type");
    exit();
}

// Sual tipləri üçün konfiqurasiya
$question_types_config = [
    7 => [
        'Əməliyyatçıların fəaliyyət istiqaməti üzrə' => 15,
        'Kart əməliyyatları istiqaməti üzrə' => 7,
        'Keyfiyyətə nəzarət istiqaməti üzrə' => 5,
        'Rəqəmsal Bankçılıq istiqaməti üzrə%' => 10,
        'Məlumat Mərkəzi istiqaməti üzrə' => 5,
        'Əməliyyatlara nəzarət istiqaməti üzrə' => 5,
        'Xəzinədarlıq istiqaməti üzrə' => 3,
        'İstehlakçılarla iş istiqaməti üzrə' => 5,
        'Komplayens istiqaməti üzrə' => 5
    ],
    // Digər sual tipləri üçün konfiqurasiyalar
];

// İmtahan vaxtını əldə et
try {
    $timer_stmt = $db->prepare("SELECT questin_time FROM quest_type WHERE idquest_type = ?");
    $timer_stmt->execute([$user_int]);
    $type_data = $timer_stmt->fetch();
    
    // Vaxt dəqiqə ilə verilir, saniyəyə çevrilir
    if ($type_data && isset($type_data['questin_time'])) {
        $exam_time_seconds = intval($type_data['questin_time']) * 60; // Dəqiqəni saniyəyə çevir
    } else {
        $exam_time_seconds = 500; // Standart 90 dəqiqə
    }
} catch (PDOException $e) {
    error_log("İmtahan vaxtı əldə edilərkən xəta: " . $e->getMessage());
    $exam_time_seconds = 3600; // Standart vaxt
}

// Sualları seç
$questions = [];

// Kateqoriyalar üzrə sual seçimi
if (isset($question_types_config[$user_int])) {
    try {
        foreach ($question_types_config[$user_int] as $category => $questionConfig) {
            // Əgər $questionConfig massivdirsə, alt kateqoriyalar var deməkdir
            if (is_array($questionConfig)) {
                foreach ($questionConfig as $subCategory => $subCategoryCount) {
                    $category_stmt = $db->prepare("
                        SELECT 
                            id_quest, 
                            quest_text, 
                            header, 
                            quest_v_A, 
                            quest_v_B, 
                            quest_v_C, 
                            quest_v_D, 
                            quest_v_Cor,
                            quest_block
                        FROM questions 
                        WHERE 
                            quest_type = ? AND 
                            header LIKE ? AND
                            header LIKE ?
                        ORDER BY RAND() 
                        LIMIT ?
                    ");
                    
                    // Kateqoriya və alt kateqoriya üçün LIKE pattern hazırla
                    $categoryPattern = '%' . $category . '%';
                    $subCategoryPattern = '%' . $subCategory . '%';
                    
                    $category_stmt->execute([$user_int, $categoryPattern, $subCategoryPattern, $subCategoryCount]);
                    $categoryQuestions = $category_stmt->fetchAll();
                    
                    // Sualları birləşdir
                    $questions = array_merge($questions, $categoryQuestions);
                }
            } else {
                // Əgər sadə kateqoriya varsa
                $category_stmt = $db->prepare("
                    SELECT 
                        id_quest, 
                        quest_text, 
                        header, 
                        quest_v_A, 
                        quest_v_B, 
                        quest_v_C, 
                        quest_v_D, 
                        quest_v_Cor,
                        quest_block
                    FROM questions 
                    WHERE 
                        quest_type = ? AND 
                        header LIKE ?
                    ORDER BY RAND() 
                    LIMIT ?
                ");
                
                // Kateqoriya üçün LIKE pattern hazırla
                $categoryPattern = '%' . $category . '%';
                
                $category_stmt->execute([$user_int, $categoryPattern, $questionConfig]);
                $categoryQuestions = $category_stmt->fetchAll();
                
                // Sualları birləşdir
                $questions = array_merge($questions, $categoryQuestions);
            }
        }
        
        // Əgər heç bir sual tapılmadısa
        if (empty($questions)) {
            throw new Exception("Heç bir sual tapılmadı");
        }
    } catch (Exception $e) {
        // Fallback: Ümumi sual seçimi
        error_log("Fallback sual seçimi: " . $e->getMessage());
        
        try {
            $fallback_stmt = $db->prepare("
                SELECT 
                    id_quest, 
                    quest_text, 
                    header, 
                    quest_v_A, 
                    quest_v_B, 
                    quest_v_C, 
                    quest_v_D, 
                    quest_v_Cor,
                    quest_block
                FROM questions 
                WHERE quest_type = ? 
                ORDER BY RAND() 
                LIMIT 20
            ");
            $fallback_stmt->execute([$user_int]);
            $questions = $fallback_stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Ümumi sual seçimi xətası: " . $e->getMessage());
            header("Location: dashboard.php?error=suallar_yuklənə_bilmədi");
            exit();
        }
    }
} else {
    // Quest_type üçün konfiqurasiya yoxdursa
    try {
        $default_stmt = $db->prepare("
            SELECT 
                id_quest, 
                quest_text, 
                header, 
                quest_v_A, 
                quest_v_B, 
                quest_v_C, 
                quest_v_D, 
                quest_v_Cor,
                quest_block
            FROM questions 
            WHERE quest_type = ? 
            ORDER BY RAND() 
            LIMIT 20
        ");
        $default_stmt->execute([$user_int]);
        $questions = $default_stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Standart sual seçimi xətası: " . $e->getMessage());
        header("Location: dashboard.php?error=suallar_yuklənə_bilmədi");
        exit();
    }
}

// Suallar yoxdursa
if (empty($questions)) {
    header("Location: dashboard.php?error=sual_yoxdur");
    exit();
}

// Sessiya məlumatlarını saxla
$_SESSION['exam_start'] = time();
$_SESSION['exam_questions'] = array_column($questions, 'id_quest');
$_SESSION['exam_time_seconds'] = $exam_time_seconds;

// Başlıqları təmizlə
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İmtahan - Rabite Exam</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #2ecc71;
            --background-color: #f4f6f9;
            --white: #ffffff;
            --text-muted: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Arial', sans-serif;
            background-color: var(--background-color);
            color: var(--primary-color);
        }

        .exam-container {
            display: flex;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px;
        }

        .exam-content {
            flex-grow: 1;
        }

        .exam-sidebar {
            position: fixed;
            bottom: 0;
            right: 0;
            width: 30%;
            background-color: #f8f9fa;
            padding: 10px ;
            border-top: 1px solid #dee2e6;
            z-index: 900;
        }

        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .exam-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .btn-exit {
            background-color: transparent;
            border: 1px solid var(--text-muted);
            color: var(--text-muted);
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-exit:hover {
            background-color: var(--text-muted);
            color: var(--white);
        }

        .question-card {
            background-color: var(--white);
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 15px;
            margin-right:360px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }

        .question-card .card-body {
            padding: 35px;
        }

        .question-header {
            display: flex;
            /* justify-content: space-between; */
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .question-number {
            background-color: var(--secondary-color);
            color: var(--white);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        .list-group-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #e0e0e0;
            margin-bottom: 10px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
            border-color: var(--secondary-color);
        }

        .list-group-item input[type="radio"] {
            margin-right: 10px;
        }

        .sidebar-category {
            margin-bottom: 15px;
        }

        .sidebar-category-title {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .sidebar-category-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-height: 450px; /* Daha yüksək maksimum hündürlük */
        }
        
        .sidebar-question-number {
            width: 35px;
            height: 35px;
            background-color: #f1f3f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sidebar-question-number:hover, 
        .sidebar-question-number.answered {
            background-color: var(--secondary-color);
            color: var(--white);
        }

        .btn-finish {
            background-color: var(--secondary-color);
            color: var(--white);
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .btn-finish:hover {
            background-color: #27ae60;
        }

        .exam-timer {
            text-align: center;
            margin-bottom: 15px;
            font-size:30px;
            border-radius:50px;
              font-weight: bold;
            background:var(--secondary-color);
            color: white ;
        }

        .category-header {
            margin-bottom: 15px;
            margin-right: 30%;
            padding: 8px 12px;
            background-color: #fff;
            border-left: 4px solid var(--secondary-color);
            border-radius: 4px;
            font-weight: 600;
        }

        .sidebar-category-questions {
            max-height: 600px; /* Mobil üçün daha böyük hündürlük */
            overflow-y: auto; /* Lazım olduqda skroll əlavə et */
        }
        .exam-sidebar {
            position: fixed;
            bottom: 0;
            right: 0;
            width: 30%;
            background-color: #f8f9fa;
            padding: 10px;
            border-top: 1px solid #dee2e6;
            z-index: 900;
            display: flex;
            flex-direction: column;
        }

        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .exam-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .btn-exit {
            background-color: transparent;
            border: 1px solid var(--text-muted);
            color: var(--text-muted);
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-exit:hover {
            background-color: var(--text-muted);
            color: var(--white);
        }

        .question-card {
            background-color: var(--white);
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 15px;
            margin-right:360px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }

        .question-card .card-body {
            padding: 35px;
        }

        .question-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .question-number {
            background-color: var(--secondary-color);
            color: var(--white);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        .list-group-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #e0e0e0;
            margin-bottom: 10px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
            border-color: var(--secondary-color);
        }

        .list-group-item input[type="radio"] {
            margin-right: 10px;
        }

        .sidebar-category {
            margin-bottom: 15px;
        }

        .sidebar-category-title {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .exam-timer {
            text-align: center;
            margin-bottom: 15px;
            font-size: 30px;
            border-radius: 50px;
            font-weight: bold;
            background: var(--secondary-color);
            color: white;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        #sidebarCategories {
            flex-grow: 1;
            overflow-y: auto;
            max-height: calc(100vh - 250px); /* Adjust based on other fixed elements */
        }
        
        .sidebar-category-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .sidebar-question-number {
            width: 35px;
            height: 35px;
            background-color: #f1f3f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sidebar-question-number:hover, 
        .sidebar-question-number.answered {
            background-color: var(--secondary-color);
            color: var(--white);
        }

        .btn-finish {
            background-color: var(--secondary-color);
            color: var(--white);
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s ease;
            flex-shrink: 0;
            margin-top: 10px;
        }

        .btn-finish:hover {
            background-color: #27ae60;
        }

        .category-header {
            margin-bottom: 15px;
            margin-right: 30%;
            padding: 8px 12px;
            background-color: #fff;
            border-left: 4px solid var(--secondary-color);
            border-radius: 4px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="exam-container">
        <div class="exam-content">
            <div class="exam-header">
                <h2 class="exam-title">İmtahan</h2>
                <button class="btn-exit" onclick="confirmExit()">İmtahanı Tərk Et</button>
            </div>

            <form id="examForm" method="POST" action="submit_exam.php">
                <div id="questions-container">
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Suallar yüklənir...</span>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn-finish d-none" id="finishBtn">İmtahanı Bitir</button>
                </div>

                <div class="exam-sidebar">
            <div class="exam-timer">
                Qalan vaxt: <span id="timerDisplay">00:00</span>
            </div>

            <div id="sidebarCategories">
                <!-- Kateqoriyalar buraya əlavə olunacaq -->
            </div>

            <button class="btn-finish mt-3" id="finishExamBtn">İmtahanı Bitir</button>
        </div>
            </form>
        </div>

       
    </div>

    <script>
        // Sualları JavaScript-ə ötür
        const questionsData = <?php echo json_encode($questions); ?>;
        
        // Global dəyişənlər
        const userInter = <?php echo $user_int; ?>;
        const answeredQuestions = {};

        // DOM elementləri
        const timerDisplay = document.getElementById('timerDisplay');
        const questionsContainer = document.getElementById('questions-container');
        const sidebarCategories = document.getElementById('sidebarCategories');
        const finishButton = document.getElementById('finishBtn');
        const finishExamBtn = document.getElementById('finishExamBtn');

        // Sualları kateqoriyalara görə qruplaşdır
        function groupQuestionsByHeader() {
            const groups = {};
            
            questionsData.forEach((question, index) => {
                // Kateqoriya adını çıxart
                let headerCategory = 'Digər Suallar';
                
                if (question.header) {
                    const headerParts = question.header.split(/[-–—:]/);
                    headerCategory = headerParts[0].trim();
                } else if (question.quest_block) {
                    headerCategory = question.quest_block;
                }
                
                if (!groups[headerCategory]) {
                    groups[headerCategory] = [];
                }
                
                groups[headerCategory].push({
                    question: question,
                    index: index
                });
            });
            
            return groups;
        }

        // Yan paneldə kateqoriyaları render et
        function renderSidebarCategories(groups) {
                sidebarCategories.innerHTML = '';
                
                Object.entries(groups).forEach(([category, questions]) => {
                    const categoryDiv = document.createElement('div');
                    categoryDiv.className = 'sidebar-category';
                    
                    const categoryTitle = document.createElement('div');
                    categoryTitle.className = 'sidebar-category-title';
                    categoryTitle.textContent = `${category} (${questions.length} sual)`;
                    categoryDiv.appendChild(categoryTitle);
                    
                    const questionsContainer = document.createElement('div');
                    questionsContainer.className = 'sidebar-category-questions';
                    
                    questions.forEach(({question, index}) => {
                        const questionNumber = document.createElement('div');
                        questionNumber.className = 'sidebar-question-number';
                        questionNumber.textContent = index + 1;
                        
                        questionNumber.addEventListener('click', () => {
                            const questionCard = document.getElementById(`question-${question.id_quest}`);
                            if (questionCard) {
                                questionCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                        });
                        
                        questionsContainer.appendChild(questionNumber);
                    });
                    
                    categoryDiv.appendChild(questionsContainer);
                    sidebarCategories.appendChild(categoryDiv);
                });
            }

        // Sualları render et
        function renderQuestions() {
            // Əvvəlcə yükləmə spinner-ını təmizlə
            questionsContainer.innerHTML = '';
            
            // Kateqoriyalara görə sualları qruplaşdır
            const groups = groupQuestionsByHeader();
            
            // Yan paneldə kateqoriyaları render et
            renderSidebarCategories(groups);
            
            // Hər kateqoriya üçün
            Object.entries(groups).forEach(([category, questions]) => {
                // Kateqoriya başlığı
                const categoryHeader = document.createElement('div');
                categoryHeader.className = 'category-header';
                categoryHeader.innerHTML = `
                    <div class="title">${category}</div>
                `;
                questionsContainer.appendChild(categoryHeader);
                
                // Kateqoriyadakı sualları əlavə et
                questions.forEach(({question, index}) => {
                    const questionCard = createQuestionCard(question, index);
                    questionsContainer.appendChild(questionCard);
                });
            });
            
            // Bitirmə düyməsini göstər
            finishButton.classList.remove('d-none');
            
            // Proqresi yenilə
            updateProgress();
            
            // Saxlanmış cavabları yüklə
            loadSavedAnswers();
        }

        // Sual kartını yarat
        function createQuestionCard(question, index) {
            const card = document.createElement('div');
            card.className = 'card question-card';
            card.id = `question-${question.id_quest}`;
            
            const cardBody = document.createElement('div');
            cardBody.className = 'card-body';
            
            // Sual başlığı
            const questionHeader = document.createElement('div');
            questionHeader.className = 'question-header';
            
            // Sual nömrəsi və block məlumatı
            const questionNumber = document.createElement('span');
            questionNumber.className = 'question-number';
            questionNumber.textContent = index + 1;
            
            // Block məlumatını əlavə et
            const questionBlockInfo = document.createElement('span');
            questionBlockInfo.className = 'ms-2 fw-bold';
            questionBlockInfo.textContent = question.quest_block || (question.header ? question.header.split(/[-–—:]/)[0].trim() : '');
            
            questionHeader.appendChild(questionNumber);
            questionHeader.appendChild(questionBlockInfo);
            cardBody.appendChild(questionHeader);
            
            // Sual mətni
            const questionText = document.createElement('p');
            questionText.textContent = question.quest_text;
            cardBody.appendChild(questionText);
            
            // Cavab variantları
            const optionsContainer = document.createElement('div');
            optionsContainer.className = 'list-group';
            
            ['A', 'B', 'C', 'D'].forEach(option => {
                const optionButton = document.createElement('label');
                optionButton.className = 'list-group-item';
                
                const radioInput = document.createElement('input');
                radioInput.type = 'radio';
                radioInput.name = `answers[${question.id_quest}]`;
                radioInput.value = option;
                radioInput.id = `q${question.id_quest}_${option}`;
                
                // Cavab seçilərkən
                radioInput.addEventListener('change', () => {
                    answeredQuestions[question.id_quest] = option;
                    updateProgress();
                    saveAnswers();
                });
                
                const optionLabel = document.createElement('span');
                optionLabel.textContent = question[`quest_v_${option}`];
                
                optionButton.appendChild(radioInput);
                optionButton.appendChild(optionLabel);
                optionsContainer.appendChild(optionButton);
            });
            
            cardBody.appendChild(optionsContainer);
            card.appendChild(cardBody);
            
            return card;
        }

        // Proqresi yenilə
        function updateProgress() {
            const totalQuestions = questionsData.length;
            const answeredCount = Object.keys(answeredQuestions).length;
            
            // Yan paneldə sual nömrələrinin stilini yenilə
            const questionNumbers = document.querySelectorAll('.sidebar-question-number');
            questionNumbers.forEach((numberEl, index) => {
                const question = questionsData[index];
                if (answeredQuestions[question.id_quest]) {
                    numberEl.classList.add('answered');
                } else {
                    numberEl.classList.remove('answered');
                }
            });
        }

        // Cavabları saxla
        function saveAnswers() {
            try {
                localStorage.setItem(`exam_answers_${userInter}`, JSON.stringify(answeredQuestions));
            } catch (error) {
                console.error('Cavabları saxlamaq mümkün olmadı:', error);
            }
        }

        // Saxlanmış cavabları yüklə
        function loadSavedAnswers() {
            try {
                const savedAnswers = localStorage.getItem(`exam_answers_${userInter}`);
                if (savedAnswers) {
                    const parsedAnswers = JSON.parse(savedAnswers);
                    
                    // Hər bir cavabı bərpa et
                    Object.entries(parsedAnswers).forEach(([questionId, option]) => {
                        const radioInput = document.getElementById(`q${questionId}_${option}`);
                        if (radioInput) {
                            radioInput.checked = true;
                            answeredQuestions[questionId] = option;
                        }
                    });
                    
                    // Proqresi yenilə
                    updateProgress();
                }
            } catch (error) {
                console.error('Cavabları yükləmək mümkün olmadı:', error);
            }
        }

        // İmtahanı tərk etmə
        function confirmExit() {
            if (confirm('İmtahanı tərk etmək istədiyinizə əminsiniz? Bu, imtahanın bitməsi ilə nəticələnəcək.')) {
                window.location.href = 'dashboard.php';
            }
        }

        // Form təqdim edilərkən
        [finishButton, finishExamBtn].forEach(button => {
            button.addEventListener('click', function(e) {
                const totalQuestions = questionsData.length;
                const answeredCount = Object.keys(answeredQuestions).length;
                
                // Cavablanmamış suallar varsa
                if (answeredCount < totalQuestions) {
                    const confirmSubmit = confirm(`Siz sadəcə ${answeredCount} / ${totalQuestions} sualı cavablandırmısınız. İmtahanı bitirmək istədiyinizə əminsiniz?`);
                    
                    if (!confirmSubmit) {
                        e.preventDefault(); // Formanın göndərilməsini dayandır
                        return false;
                    }
                }
                
                // Lokaldan məlumatları təmizlə
                localStorage.removeItem(`exam_answers_${userInter}`);
                localStorage.removeItem(`exam_time_left_${userInter}`);
            });
        });

        // Səhifə yenilənərkən xəbərdarlıq
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '';
        });

        // --------------------------
        // YENİ TIMER İMPLEMENTASİYASI
        // --------------------------
        
        document.addEventListener('DOMContentLoaded', function() {
            // Timer dəyişənləri
            let timeLeft = <?php echo $exam_time_seconds; ?>;
            
            // Vaxtı yenilə
            function updateTimer() {
                // Vaxtı formatla
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                const seconds = timeLeft % 60;
                
                // Formatlanmış vaxtı göstər
                let formattedTime = '';
                if (hours > 0) {
                    formattedTime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                } else {
                    formattedTime = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                }
                
                timerDisplay.textContent = formattedTime;
            }
            
            // İlk vaxtı göstər
            updateTimer();
            
            // Timer intervalı
            const timerInterval = setInterval(function() {
                timeLeft--;
                updateTimer();
                
                // Hər 10 saniyədə bir saxla
                if (timeLeft % 10 === 0) {
                    localStorage.setItem(`exam_time_left_${userInter}`, timeLeft);
                }
                
                // Vaxt bitdikdə
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    alert('İmtahan vaxtı bitdi!');
                    document.getElementById('examForm').submit();
                }
            }, 1000);
            
            // Saxlanmış vaxtı yüklə
            const savedTime = localStorage.getItem(`exam_time_left_${userInter}`);
            if (savedTime) {
                const parsedTime = parseInt(savedTime);
                if (!isNaN(parsedTime) && parsedTime > 0 && parsedTime < <?php echo $exam_time_seconds; ?>) {
                    timeLeft = parsedTime;
                    updateTimer();
                }
            }
        });

        // Səhifə yüklənərkən sualları render et
        document.addEventListener('DOMContentLoaded', renderQuestions);
    </script>
</body>
</html>