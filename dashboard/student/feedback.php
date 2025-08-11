<?php
require_once __DIR__ . '/../../includes/path_fix.php';
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/Database.php';
require_once $base_path . '/includes/utility.php';
requireLogin();
requireRole('student');

$user_id = $_SESSION['user_id'];
$consultation_id = (int)($_GET['id'] ?? 0);
if (!$consultation_id) {
    setMessage('Invalid consultation ID','danger');
    redirect('consultations.php');
    exit;
}
$db = (new Database())->getConnection();
// Verify ownership and status
$stmt = $db->prepare('SELECT cr.*, u.first_name as counselor_first_name, u.last_name as counselor_last_name FROM consultation_requests cr LEFT JOIN users u ON cr.counselor_id = u.user_id WHERE cr.id=? AND cr.student_id=? AND cr.status="completed"');
$stmt->execute([$consultation_id,$user_id]);
$consultation = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$consultation){
    setMessage('Consultation not found or not eligible for feedback.','danger');
    redirect('consultations.php');
    exit;
}

// Check for existing feedback
$stmt = $db->prepare('SELECT * FROM feedback WHERE consultation_id = ? AND student_id = ?');
$stmt->execute([$consultation_id, $user_id]);
$existing_feedback = $stmt->fetch(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $rating = (int)($_POST['rating']??0);
    $comments = sanitizeInput($_POST['comments']??'');
    if($rating<1||$rating>5){
        setMessage('Please select a rating between 1 and 5.','danger');
    }else{
        if($existing_feedback) {
            // Update existing feedback
            $db->prepare('UPDATE feedback SET rating = ?, comments = ?, created_at = NOW() WHERE consultation_id = ? AND student_id = ?')
                ->execute([$rating,$comments,$consultation_id,$user_id]);
            setMessage('Your feedback has been updated successfully!','success');
        } else {
            // Insert new feedback
            $db->prepare('INSERT INTO feedback (consultation_id, student_id, rating, comments) VALUES (?,?,?,?)')
                ->execute([$consultation_id,$user_id,$rating,$comments]);
            setMessage('Thank you for your feedback!','success');
        }
        redirect('consultations.php');
        exit;
    }
}
$page_title = $existing_feedback ? 'Edit Feedback' : 'Submit Feedback';
include_once $base_path.'/includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/dashboard/student/">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/dashboard/student/consultations.php">Consultations</a></li>
            <li class="breadcrumb-item active">Feedback</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <!-- Header Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center py-5">
                    <div class="icon-circle bg-primary text-white mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-star" style="font-size: 2rem;"></i>
                    </div>
                    <h2 class="card-title text-primary mb-2"><?php echo $existing_feedback ? 'Update Your Feedback' : 'Share Your Experience'; ?></h2>
                    <p class="lead text-muted">Help us improve our counseling services by sharing your experience</p>
                </div>
            </div>

            <!-- Consultation Info Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clipboard-list me-2 text-info"></i>
                        Consultation Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Consultation ID:</strong> #<?php echo $consultation_id; ?></p>
                            <p class="mb-2"><strong>Date:</strong> <?php echo formatDate($consultation['preferred_date'], 'M d, Y'); ?></p>
                            <p class="mb-0"><strong>Time:</strong> <?php echo formatTime($consultation['preferred_time']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Counselor:</strong> 
                                <?php if($consultation['counselor_first_name']): ?>
                                    <?php echo $consultation['counselor_first_name'] . ' ' . $consultation['counselor_last_name']; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </p>
                            <p class="mb-2"><strong>Issue Category:</strong> <?php echo $consultation['issue_category'] ?: 'General'; ?></p>
                            <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success">Completed</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Form Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star me-2"></i>
                        <?php echo $existing_feedback ? 'Update Your Rating & Comments' : 'Rate Your Experience'; ?>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" action="" class="needs-validation" novalidate>
                        <!-- Rating Section -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-3">
                                <i class="fas fa-star text-warning me-2"></i>Overall Rating
                                <span class="text-danger">*</span>
                            </label>
                            <div class="rating-container d-flex justify-content-center mb-3">
                                <?php for($i=1;$i<=5;$i++): ?>
                                    <div class="form-check mx-2">
                                        <input class="form-check-input d-none" type="radio" name="rating" id="rate<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($existing_feedback && $existing_feedback['rating'] == $i) ? 'checked' : ''; ?> required>
                                        <label class="form-check-label star-label" for="rate<?php echo $i; ?>" data-rating="<?php echo $i; ?>">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="text-center">
                                <small class="text-muted rating-text">Click on the stars to rate your experience</small>
                            </div>
                        </div>

                        <!-- Comments Section -->
                        <div class="mb-4">
                            <label for="comments" class="form-label fw-bold">
                                <i class="fas fa-comment text-info me-2"></i>Your Comments
                                <small class="text-muted fw-normal">(Optional)</small>
                            </label>
                            <textarea class="form-control form-control-lg" 
                                      id="comments" 
                                      name="comments" 
                                      rows="5" 
                                      placeholder="Tell us about your experience. What went well? How can we improve?"><?php echo $existing_feedback ? htmlspecialchars($existing_feedback['comments']) : ''; ?></textarea>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Your feedback is confidential and helps us improve our services.
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?php echo SITE_URL; ?>/dashboard/student/consultations.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Back to Consultations
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i><?php echo $existing_feedback ? 'Update Feedback' : 'Submit Feedback'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if($existing_feedback): ?>
            <!-- Previous Feedback Info -->
            <div class="card border-0 bg-light mt-4">
                <div class="card-body text-center py-3">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Last updated: <?php echo formatDate($existing_feedback['created_at'], 'M d, Y h:i A'); ?>
                    </small>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.star-label {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: all 0.2s ease;
}

.star-label:hover,
.star-label.active {
    color: #ffc107;
    transform: scale(1.1);
}

.form-check-input:checked + .star-label {
    color: #ffc107;
}

.rating-container .form-check-input:checked ~ .form-check .star-label {
    color: #ddd;
}

.needs-validation .form-control:invalid {
    border-color: #dc3545;
}

.needs-validation .form-control:valid {
    border-color: #28a745;
}

@media (max-width: 768px) {
    .star-label {
        font-size: 1.5rem;
    }
    
    .rating-container .form-check {
        margin: 0 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star-label');
    const ratingText = document.querySelector('.rating-text');
    const ratingDescriptions = {
        1: 'Poor - Very unsatisfied',
        2: 'Fair - Somewhat unsatisfied', 
        3: 'Good - Neutral experience',
        4: 'Very Good - Satisfied',
        5: 'Excellent - Very satisfied'
    };

    // Initialize with existing rating if any
    const checkedInput = document.querySelector('input[name="rating"]:checked');
    if (checkedInput) {
        updateStars(checkedInput.value);
    }

    stars.forEach((star, index) => {
        const rating = index + 1;
        
        star.addEventListener('mouseover', function() {
            updateStars(rating, true);
            ratingText.textContent = ratingDescriptions[rating];
        });
        
        star.addEventListener('mouseout', function() {
            const checkedInput = document.querySelector('input[name="rating"]:checked');
            if (checkedInput) {
                updateStars(checkedInput.value);
                ratingText.textContent = ratingDescriptions[checkedInput.value];
            } else {
                resetStars();
                ratingText.textContent = 'Click on the stars to rate your experience';
            }
        });
        
        star.addEventListener('click', function() {
            document.getElementById('rate' + rating).checked = true;
            updateStars(rating);
            ratingText.textContent = ratingDescriptions[rating];
        });
    });

    function updateStars(rating, hover = false) {
        stars.forEach((star, index) => {
            const starRating = index + 1;
            if (starRating <= rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    function resetStars() {
        stars.forEach(star => {
            star.classList.remove('active');
        });
    }

    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
});
</script>

<?php include_once $base_path.'/includes/footer.php'; ?> 