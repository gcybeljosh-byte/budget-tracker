<?php
$pageTitle = 'AI Chat History';
$pageHeader = 'Chat History';
include 'includes/header.php';
require_once 'includes/db.php';

// Fetch Chat History
$user_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT id, message, sender, created_at FROM ai_chat_history WHERE user_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

// Group messages by date
$groupedMessages = [];
foreach ($messages as $msg) {
    $date = date('Y-m-d', strtotime($msg['created_at']));
    $displayDate = $date;
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($date === $today) {
        $displayDate = 'Today';
    } elseif ($date === $yesterday) {
        $displayDate = 'Yesterday';
    } else {
        $displayDate = date('F j, Y', strtotime($msg['created_at']));
    }

    if (!isset($groupedMessages[$displayDate])) {
        $groupedMessages[$displayDate] = [];
    }
    $groupedMessages[$displayDate][] = $msg;
}
?>

    <?php include 'includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">

        <?php include 'includes/navbar.php'; ?>

        <div class="container-fluid px-4 py-4">

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden" style="height: 80vh; display: flex; flex-direction: column;">
                <!-- Header -->
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary-subtle text-primary p-2 rounded-3 me-3">
                            <i class="fas fa-history fs-5"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">Conversation Log</h5>
                            <p class="text-muted small mb-0"><?php echo count($messages); ?> messages recorded</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0" style="max-width: 400px;">
                        <div class="input-group input-group-sm rounded-pill border overflow-hidden bg-light">
                            <span class="input-group-text bg-transparent border-0 text-secondary"><i class="fas fa-search"></i></span>
                            <input type="text" id="chatSearch" class="form-control bg-transparent border-0" placeholder="Search messages...">
                        </div>
                        <?php if (!empty($messages)): ?>
                        <button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="deleteHistory()">
                            <i class="fas fa-trash-alt me-1"></i> Clear
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Body -->
                <div class="card-body p-0 overflow-auto flex-grow-1" id="chatBody" style="background: #fdfdfd; scroll-behavior: smooth;">
                    
                    <?php if (empty($messages)): ?>
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center p-5">
                            <div class="bg-light rounded-circle p-4 mb-4" style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-comments fa-4x text-muted opacity-25"></i>
                            </div>
                            <h4 class="fw-bold text-dark">No Chat History</h4>
                            <p class="text-muted mx-auto" style="max-width: 300px;">Start a conversation with your AI Budget Assistant to see it recorded here.</p>
                            <a href="index.php" class="btn btn-primary rounded-pill px-4 mt-2">Go to Dashboard</a>
                        </div>
                    <?php else: ?>
                        <div class="p-4">
                            <?php foreach ($groupedMessages as $date => $msgs): ?>
                                <div class="date-group-header text-center my-4 position-relative">
                                    <hr class="position-absolute w-100 top-50 start-0 translate-middle-y opacity-10">
                                    <span class="bg-white px-3 text-secondary small fw-bold position-relative z-1"><?php echo $date; ?></span>
                                </div>

                                <div class="d-flex flex-column gap-4">
                                    <?php foreach ($msgs as $msg): ?>
                                        <?php 
                                            $isUser = $msg['sender'] === 'user';
                                            $align = $isUser ? 'align-self-end' : 'align-self-start';
                                            $bubbleClass = $isUser ? 'bg-primary text-white' : 'bg-white border text-dark';
                                            $bubbleRadius = $isUser ? 'rounded-start-4 rounded-top-4' : 'rounded-end-4 rounded-top-4';
                                            $avatar = $isUser ? 'fas fa-user-circle' : 'fas fa-robot';
                                            $avatarColor = $isUser ? 'text-primary' : 'text-success';
                                        ?>
                                        <div class="message-wrapper d-flex gap-3 <?php echo $align; ?> <?php echo $isUser ? 'flex-row-reverse' : ''; ?>" style="max-width: 85%;" data-content="<?php echo htmlspecialchars(strtolower($msg['message'])); ?>">
                                            <div class="avatar-sm flex-shrink-0 mt-auto mb-1">
                                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;">
                                                    <i class="<?php echo $avatar; ?> <?php echo $avatarColor; ?> small"></i>
                                                </div>
                                            </div>
                                            <div class="bubble-container d-flex flex-column <?php echo $isUser ? 'align-items-end' : 'align-items-start'; ?>">
                                                <div class="px-3 py-2 shadow-sm <?php echo $bubbleClass; ?> <?php echo $bubbleRadius; ?> mb-1" style="font-size: 0.95rem; line-height: 1.5;">
                                                    <?php 
                                                        $formattedMsg = htmlspecialchars($msg['message']);
                                                        // Bold
                                                        $formattedMsg = preg_replace('/\*\*(.*?)\*\*/', '<strong class="fw-bold">$1</strong>', $formattedMsg);
                                                        // Bullet lists
                                                        if (strpos($formattedMsg, "\n* ") !== false || strpos($formattedMsg, "* ") === 0) {
                                                            $formattedMsg = preg_replace('/^\* (.*)/m', '• $1', $formattedMsg);
                                                        }
                                                        echo nl2br($formattedMsg); 
                                                    ?>
                                                </div>
                                                <div class="message-time text-muted px-1" style="font-size: 0.7rem;">
                                                    <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Footer Info -->
                <div class="card-footer bg-light border-0 py-2 text-center">
                    <span class="text-muted small opacity-75">
                        <i class="fas fa-lock me-1"></i> Conversations are stored for your record and analyzed for budget insights.
                    </span>
                </div>
            </div>

        </div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to bottom
    const chatBody = document.getElementById('chatBody');
    if (chatBody) {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    // Search Filtering
    const searchInput = document.getElementById('chatSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const messages = document.querySelectorAll('.message-wrapper');
            const dateGroups = document.querySelectorAll('.date-group-header');
            
            messages.forEach(msg => {
                const content = msg.getAttribute('data-content');
                if (content.includes(query)) {
                    msg.classList.remove('d-none');
                } else {
                    msg.classList.add('d-none');
                }
            });

            // Hide date headers if all messages in group are hidden
            dateGroups.forEach(group => {
                let current = group.nextElementSibling;
                let hasVisible = false;
                
                // Content of a date group is a d-flex container
                if (current && current.classList.contains('d-flex')) {
                    const groupMessages = current.querySelectorAll('.message-wrapper');
                    groupMessages.forEach(m => {
                        if (!m.classList.contains('d-none')) hasVisible = true;
                    });
                }
                
                if (hasVisible) {
                    group.classList.remove('d-none');
                    if (current) current.classList.remove('d-none');
                } else {
                    group.classList.add('d-none');
                    if (current) current.classList.add('d-none');
                }
            });
        });
    }
});

function deleteHistory() {
    Swal.fire({
        title: 'Clear Chat History?',
        text: "This will permanently remove all conversation logs. This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, clear it'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/chat_history.php', {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Cleared!',
                        text: 'Your chat history has been securely removed.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error!', data.message || 'Failed to delete history.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'An unexpected error occurred.', 'error');
            });
        }
    })
}
</script>

<?php include 'includes/footer.php'; ?>
