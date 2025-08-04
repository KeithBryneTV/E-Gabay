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
$stmt = $db->prepare('SELECT * FROM consultation_requests WHERE id=? AND student_id=? AND status="completed"');
$stmt->execute([$consultation_id,$user_id]);
$consultation = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$consultation){
    setMessage('Consultation not found or not eligible for feedback.','danger');
    redirect('consultations.php');
    exit;
}
// Prevent duplicate feedback
if(hasFeedback($consultation_id,$user_id)){
    setMessage('Feedback already submitted for this consultation.','info');
    redirect('consultations.php');
    exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $rating = (int)($_POST['rating']??0);
    $comments = sanitizeInput($_POST['comments']??'');
    if($rating<1||$rating>5){
        setMessage('Please select a rating between 1 and 5.','danger');
    }else{
        $db->prepare('INSERT INTO feedback (consultation_id, student_id, rating, comments) VALUES (?,?,?,?)')
            ->execute([$consultation_id,$user_id,$rating,$comments]);
        setMessage('Thank you for your feedback!','success');
        redirect('consultations.php');
        exit;
    }
}
$page_title='Submit Feedback';
include_once $base_path.'/includes/header.php';
?>
<div class="row">
    <div class="col-lg-8 mx-auto">
        <h3 class="mb-4">Feedback for Consultation #<?php echo $consultation_id; ?></h3>
        <?php displayMessage(); ?>
        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label">Rating</label><br>
                <?php for($i=1;$i<=5;$i++): ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="rating" id="rate<?php echo $i; ?>" value="<?php echo $i; ?>">
                        <label class="form-check-label" for="rate<?php echo $i; ?>">
                            <?php echo $i; ?> <i class="fas fa-star text-warning"></i>
                        </label>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="mb-3">
                <label for="comments" class="form-label">Comments (optional)</label>
                <textarea class="form-control" id="comments" name="comments" rows="4"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Feedback</button>
            <a href="consultations.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php include_once $base_path.'/includes/footer.php'; ?> 