<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';


//Not working right now


requireLogin();

$pageTitle = "Communications";
$message = '';
$message_type = '';

// Get current user ID
$current_user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_message'])) {
        // Send new message
        $subject = $conn->real_escape_string($_POST['subject']);
        $message_content = $conn->real_escape_string($_POST['message']);
        $message_type = $conn->real_escape_string($_POST['message_type']);
        $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
        $recipient_ids = $_POST['recipients'] ?? [];
        
        if (empty($subject) || empty($message_content) || empty($recipient_ids)) {
            $message = "Please fill all required fields and select at least one recipient.";
            $message_type = 'danger';
        } else {
            // Insert main message
            $sql = "INSERT INTO communications (subject, message, sender_id, message_type, is_urgent) 
                    VALUES ('$subject', '$message_content', $current_user_id, '$message_type', $is_urgent)";
            
            if ($conn->query($sql)) {
                $message_id = $conn->insert_id;
                
                // Insert recipients
                foreach ($recipient_ids as $recipient_id) {
                    $recipient_id = (int)$recipient_id;
                    $recipient_sql = "INSERT INTO message_recipients (message_id, recipient_id) 
                                     VALUES ($message_id, $recipient_id)";
                    $conn->query($recipient_sql);
                }
                
                $message = "Message sent successfully!";
                $message_type = 'success';
            } else {
                $message = "Error sending message: " . $conn->error;
                $message_type = 'danger';
            }
        }
    }
    elseif (isset($_POST['reply_message'])) {
        // Reply to message
        $parent_id = (int)$_POST['parent_id'];
        $reply_content = $conn->real_escape_string($_POST['reply_content']);
        
        // Get original message details
        $parent_sql = "SELECT subject, sender_id, message_type FROM communications WHERE id = $parent_id";
        $parent_result = $conn->query($parent_sql);
        
        if ($parent_result->num_rows > 0) {
            $parent = $parent_result->fetch_assoc();
            $subject = "Re: " . $parent['subject'];
            $recipient_id = $parent['sender_id']; // Reply to sender
            
            $sql = "INSERT INTO communications (subject, message, sender_id, recipient_id, message_type, parent_id) 
                    VALUES ('$subject', '$reply_content', $current_user_id, $recipient_id, '{$parent['message_type']}', $parent_id)";
            
            if ($conn->query($sql)) {
                $reply_id = $conn->insert_id;
                
                // Add recipient
                $recipient_sql = "INSERT INTO message_recipients (message_id, recipient_id) 
                                 VALUES ($reply_id, $recipient_id)";
                $conn->query($recipient_sql);
                
                $message = "Reply sent successfully!";
                $message_type = 'success';
            } else {
                $message = "Error sending reply: " . $conn->error;
                $message_type = 'danger';
            }
        }
    }
    elseif (isset($_POST['mark_read'])) {
        // Mark message as read
        $message_id = (int)$_POST['message_id'];
        $sql = "UPDATE message_recipients SET is_read = TRUE, read_time = NOW() 
                WHERE message_id = $message_id AND recipient_id = $current_user_id";
        
        if ($conn->query($sql)) {
            $message = "Message marked as read!";
            $message_type = 'success';
        }
    }
    elseif (isset($_POST['archive_message'])) {
        // Archive message
        $message_id = (int)$_POST['message_id'];
        $sql = "UPDATE communications SET is_archived = TRUE WHERE id = $message_id";
        
        if ($conn->query($sql)) {
            $message = "Message archived!";
            $message_type = 'success';
        }
    }
    elseif (isset($_POST['delete_message'])) {
        // Delete message for recipient
        $message_id = (int)$_POST['message_id'];
        $sql = "UPDATE message_recipients SET is_deleted = TRUE 
                WHERE message_id = $message_id AND recipient_id = $current_user_id";
        
        if ($conn->query($sql)) {
            $message = "Message deleted!";
            $message_type = 'success';
        }
    }
}

// Get filter parameters
$folder = isset($_GET['folder']) ? $conn->real_escape_string($_GET['folder']) : 'inbox';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';
$urgent = isset($_GET['urgent']) ? 1 : 0;
$unread = isset($_GET['unread']) ? 1 : 0;

// Build query based on folder
switch ($folder) {
    case 'sent':
        $sql = "SELECT c.*, 
                GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') as recipient_names,
                (SELECT COUNT(*) FROM communications WHERE parent_id = c.id) as reply_count
                FROM communications c
                LEFT JOIN message_recipients mr ON c.id = mr.message_id
                LEFT JOIN users u ON mr.recipient_id = u.id
                WHERE c.sender_id = $current_user_id
                AND (c.parent_id IS NULL OR c.parent_id = 0)";
        break;
    
    case 'drafts':
        $sql = "SELECT c.*, u.name as recipient_name
                FROM communications c
                LEFT JOIN users u ON c.recipient_id = u.id
                WHERE c.sender_id = $current_user_id
                AND c.is_draft = TRUE";
        break;
    
    case 'archived':
        $sql = "SELECT c.*, u.name as sender_name,
                (SELECT COUNT(*) FROM message_recipients WHERE message_id = c.id AND recipient_id = $current_user_id AND is_read = FALSE) as is_unread
                FROM communications c
                JOIN users u ON c.sender_id = u.id
                JOIN message_recipients mr ON c.id = mr.message_id
                WHERE mr.recipient_id = $current_user_id
                AND mr.is_deleted = FALSE
                AND c.is_archived = TRUE";
        break;
    
    case 'inbox':
    default:
        $sql = "SELECT c.*, u.name as sender_name,
                mr.is_read,
                mr.read_time,
                (SELECT COUNT(*) FROM communications WHERE parent_id = c.id) as reply_count
                FROM communications c
                JOIN users u ON c.sender_id = u.id
                JOIN message_recipients mr ON c.id = mr.message_id
                WHERE mr.recipient_id = $current_user_id
                AND mr.is_deleted = FALSE
                AND c.is_archived = FALSE";
        break;
}

// Apply filters
if (!empty($search)) {
    $sql .= " AND (c.subject LIKE '%$search%' OR c.message LIKE '%$search%' OR u.name LIKE '%$search%')";
}
if (!empty($type)) {
    $sql .= " AND c.message_type = '$type'";
}
if ($urgent) {
    $sql .= " AND c.is_urgent = TRUE";
}
if ($unread && $folder === 'inbox') {
    $sql .= " AND mr.is_read = FALSE";
}

$sql .= " ORDER BY c.created_at DESC, c.is_urgent DESC";

$messages_result = $conn->query($sql);

// Get message statistics
$stats_sql = "SELECT 
              (SELECT COUNT(*) FROM message_recipients mr 
               JOIN communications c ON mr.message_id = c.id 
               WHERE mr.recipient_id = $current_user_id 
               AND mr.is_read = FALSE 
               AND mr.is_deleted = FALSE
               AND c.is_archived = FALSE) as unread_count,
              
              (SELECT COUNT(*) FROM message_recipients mr 
               JOIN communications c ON mr.message_id = c.id 
               WHERE mr.recipient_id = $current_user_id 
               AND c.is_urgent = TRUE
               AND mr.is_read = FALSE 
               AND mr.is_deleted = FALSE) as urgent_unread,
              
              (SELECT COUNT(*) FROM communications 
               WHERE sender_id = $current_user_id) as sent_count,
              
              (SELECT COUNT(*) FROM message_recipients mr 
               JOIN communications c ON mr.message_id = c.id 
               WHERE mr.recipient_id = $current_user_id 
               AND mr.is_deleted = FALSE
               AND c.is_archived = TRUE) as archived_count";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get all users for recipient dropdown (except current user)
$users_sql = "SELECT id, name, role, airline FROM users WHERE id != $current_user_id ORDER BY name";
$users_result = $conn->query($users_sql);

// Get conversation threads if viewing a specific message
$thread_id = isset($_GET['thread']) ? (int)$_GET['thread'] : 0;
$thread_messages = [];
if ($thread_id > 0) {
    $thread_sql = "SELECT c.*, u.name as sender_name, u.role as sender_role,
                   CASE WHEN c.sender_id = $current_user_id THEN 'sent' ELSE 'received' END as message_direction
                   FROM communications c
                   JOIN users u ON c.sender_id = u.id
                   WHERE c.id = $thread_id OR c.parent_id = $thread_id
                   ORDER BY c.created_at ASC";
    $thread_result = $conn->query($thread_sql);
    while ($msg = $thread_result->fetch_assoc()) {
        $thread_messages[] = $msg;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Airport Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .comms-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .comms-sidebar {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .comms-main {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .folder-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .folder-item {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s;
        }
        .folder-item:hover, .folder-item.active {
            background: #f0f8ff;
            color: var(--primary);
        }
        .folder-item.active {
            font-weight: bold;
            border-left: 4px solid var(--primary);
        }
        .folder-badge {
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
        }
        .message-list {
            max-height: 600px;
            overflow-y: auto;
        }
        .message-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.3s;
        }
        .message-item:hover {
            background: #f9f9f9;
        }
        .message-item.unread {
            background: #e7f3ff;
            border-left: 4px solid var(--primary);
        }
        .message-item.urgent {
            background: #fff3cd;
            border-left: 4px solid #dc3545;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .message-sender {
            font-weight: bold;
            color: var(--secondary);
        }
        .message-time {
            color: #666;
            font-size: 0.9rem;
        }
        .message-subject {
            font-weight: 500;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .message-preview {
            color: #666;
            font-size: 0.9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .message-tags {
            display: flex;
            gap: 5px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .message-tag {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            background: #e9ecef;
            color: #495057;
        }
        .message-tag.urgent {
            background: #dc3545;
            color: white;
        }
        .message-tag.emergency {
            background: #fd7e14;
            color: white;
        }
        .message-tag.service {
            background: #20c997;
            color: white;
        }
        .thread-container {
            max-height: 500px;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .thread-message {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            max-width: 80%;
        }
        .thread-message.sent {
            background: #d4edda;
            margin-left: auto;
            border: 1px solid #c3e6cb;
        }
        .thread-message.received {
            background: white;
            margin-right: auto;
            border: 1px solid #e9ecef;
        }
        .thread-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        .thread-sender {
            font-weight: bold;
        }
        .thread-time {
            color: #666;
        }
        .compose-btn {
            width: 100%;
            margin-bottom: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 800px;
            position: relative;
            max-height: 85vh;
            overflow-y: auto;
        }
        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .recipient-select {
            min-height: 100px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
        }
        .recipient-option {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .recipient-option:last-child {
            border-bottom: none;
        }
        @media (max-width: 768px) {
            .comms-container {
                grid-template-columns: 1fr;
            }
            .thread-message {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-comments"></i> Communications</h1>
            <p>Airport messaging and notification system</p>
            <div class="user-role">
                <span class="role-badge role-<?php echo $_SESSION['user_role']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $_SESSION['user_role'])); ?>
                </span>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Message Statistics -->
        <div class="service-stats">
            <div class="stat-card">
                <h3><?php echo $stats['unread_count']; ?></h3>
                <p>Unread Messages</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['urgent_unread']; ?></h3>
                <p>Urgent Unread</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['sent_count']; ?></h3>
                <p>Sent Messages</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['archived_count']; ?></h3>
                <p>Archived</p>
            </div>
        </div>
        
        <!-- Main Communications Interface -->
        <div class="comms-container">
            <!-- Sidebar -->
            <div class="comms-sidebar">
                <button class="btn btn-primary compose-btn" onclick="openComposeModal()">
                    <i class="fas fa-edit"></i> Compose Message
                </button>
                
                <h3><i class="fas fa-folder"></i> Folders</h3>
                <ul class="folder-list">
                    <li class="folder-item <?php echo $folder === 'inbox' ? 'active' : ''; ?>" 
                        onclick="window.location='communications.php?folder=inbox'">
                        <span><i class="fas fa-inbox"></i> Inbox</span>
                        <?php if ($stats['unread_count'] > 0): ?>
                            <span class="folder-badge"><?php echo $stats['unread_count']; ?></span>
                        <?php endif; ?>
                    </li>
                    <li class="folder-item <?php echo $folder === 'sent' ? 'active' : ''; ?>" 
                        onclick="window.location='communications.php?folder=sent'">
                        <span><i class="fas fa-paper-plane"></i> Sent</span>
                        <?php if ($stats['sent_count'] > 0): ?>
                            <span class="folder-badge"><?php echo $stats['sent_count']; ?></span>
                        <?php endif; ?>
                    </li>
                    <li class="folder-item <?php echo $folder === 'archived' ? 'active' : ''; ?>" 
                        onclick="window.location='communications.php?folder=archived'">
                        <span><i class="fas fa-archive"></i> Archived</span>
                        <?php if ($stats['archived_count'] > 0): ?>
                            <span class="folder-badge"><?php echo $stats['archived_count']; ?></span>
                        <?php endif; ?>
                    </li>
                </ul>
                
                <h3><i class="fas fa-filter"></i> Filters</h3>
                <form method="GET" action="" class="filter-form">
                    <input type="hidden" name="folder" value="<?php echo $folder; ?>">
                    <div class="form-group">
                        <input type="text" name="search" class="form-control" placeholder="Search messages..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <select name="type" class="form-control">
                            <option value="">All Message Types</option>
                            <option value="general" <?php echo $type === 'general' ? 'selected' : ''; ?>>General</option>
                            <option value="service_request" <?php echo $type === 'service_request' ? 'selected' : ''; ?>>Service Request</option>
                            <option value="emergency" <?php echo $type === 'emergency' ? 'selected' : ''; ?>>Emergency</option>
                            <option value="schedule_change" <?php echo $type === 'schedule_change' ? 'selected' : ''; ?>>Schedule Change</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="urgent" value="1" <?php echo $urgent ? 'checked' : ''; ?>>
                            Urgent Only
                        </label>
                    </div>
                    <?php if ($folder === 'inbox'): ?>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="unread" value="1" <?php echo $unread ? 'checked' : ''; ?>>
                            Unread Only
                        </label>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                    <a href="communications.php" class="btn btn-secondary btn-block">Clear Filters</a>
                </form>
            </div>
            
            <!-- Main Content -->
            <div class="comms-main">
                <?php if ($thread_id > 0 && !empty($thread_messages)): ?>
                    <!-- Thread View -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2>Conversation Thread</h2>
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back to Messages
                        </button>
                    </div>
                    
                    <div class="thread-container">
                        <?php foreach ($thread_messages as $thread_msg): ?>
                            <div class="thread-message <?php echo $thread_msg['message_direction']; ?>">
                                <div class="thread-header">
                                    <span class="thread-sender">
                                        <?php echo $thread_msg['sender_name']; ?>
                                        <small class="role-badge role-<?php echo $thread_msg['sender_role']; ?>" style="font-size: 0.7rem;">
                                            <?php echo ucfirst(str_replace('_', ' ', $thread_msg['sender_role'])); ?>
                                        </small>
                                    </span>
                                    <span class="thread-time">
                                        <?php echo date('M j, H:i', strtotime($thread_msg['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($thread_msg['message'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Reply Form -->
                    <form method="POST">
                        <input type="hidden" name="parent_id" value="<?php echo $thread_id; ?>">
                        <div class="form-group">
                            <textarea name="reply_content" class="form-control" rows="4" 
                                      placeholder="Type your reply here..." required></textarea>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="reply_message" class="btn btn-primary">
                                <i class="fas fa-reply"></i> Send Reply
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                Cancel
                            </button>
                        </div>
                    </form>
                    
                <?php else: ?>
                    <!-- Message List View -->
                    <h2>
                        <?php echo ucfirst($folder); ?>
                        <small class="text-muted">(<?php echo $messages_result->num_rows; ?> messages)</small>
                    </h2>
                    
                    <?php if ($messages_result->num_rows > 0): ?>
                        <div class="message-list">
                            <?php while($msg = $messages_result->fetch_assoc()): ?>
                                <div class="message-item <?php echo (isset($msg['is_read']) && !$msg['is_read']) ? 'unread' : ''; ?> <?php echo ($msg['is_urgent']) ? 'urgent' : ''; ?>"
                                     onclick="window.location='communications.php?folder=<?php echo $folder; ?>&thread=<?php echo $msg['id']; ?>'">
                                    <div class="message-header">
                                        <span class="message-sender">
                                            <?php if ($folder === 'sent'): ?>
                                                To: <?php echo $msg['recipient_names'] ?: 'Multiple recipients'; ?>
                                            <?php else: ?>
                                                <?php echo $msg['sender_name']; ?>
                                            <?php endif; ?>
                                        </span>
                                        <span class="message-time">
                                            <?php echo date('M j, H:i', strtotime($msg['created_at'])); ?>
                                            <?php if ($msg['is_urgent']): ?>
                                                <i class="fas fa-exclamation-triangle text-danger"></i>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="message-subject">
                                        <?php echo htmlspecialchars($msg['subject']); ?>
                                        <?php if (isset($msg['reply_count']) && $msg['reply_count'] > 0): ?>
                                            <span class="badge bg-info">
                                                <?php echo $msg['reply_count']; ?> replies
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-preview">
                                        <?php echo substr(strip_tags($msg['message']), 0, 150); ?>...
                                    </div>
                                    <div class="message-tags">
                                        <span class="message-tag">
                                            <?php echo ucfirst(str_replace('_', ' ', $msg['message_type'])); ?>
                                        </span>
                                        <?php if ($msg['is_urgent']): ?>
                                            <span class="message-tag urgent">URGENT</span>
                                        <?php endif; ?>
                                        <?php if ($msg['message_type'] === 'emergency'): ?>
                                            <span class="message-tag emergency">EMERGENCY</span>
                                        <?php endif; ?>
                                        <?php if ($msg['message_type'] === 'service_request'): ?>
                                            <span class="message-tag service">SERVICE</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-envelope-open-text" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                            <h3>No Messages Found</h3>
                            <p>
                                <?php if ($folder === 'inbox'): ?>
                                    Your inbox is empty. Send a message to get started!
                                <?php elseif ($folder === 'sent'): ?>
                                    You haven't sent any messages yet.
                                <?php elseif ($folder === 'archived'): ?>
                                    No archived messages.
                                <?php else: ?>
                                    No messages in this folder.
                                <?php endif; ?>
                            </p>
                            <?php if ($folder === 'inbox'): ?>
                                <button class="btn btn-primary" onclick="openComposeModal()">
                                    <i class="fas fa-edit"></i> Compose Your First Message
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Compose Message Modal -->
    <div id="composeModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('composeModal')">&times;</span>
            <h2><i class="fas fa-edit"></i> Compose New Message</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Recipients *</label>
                    <div class="recipient-select">
                        <?php while($user = $users_result->fetch_assoc()): ?>
                            <div class="recipient-option">
                                <label>
                                    <input type="checkbox" name="recipients[]" value="<?php echo $user['id']; ?>">
                                    <?php echo $user['name']; ?> 
                                    <small class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </small>
                                    <?php if ($user['airline']): ?>
                                        <small>(<?php echo $user['airline']; ?>)</small>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Subject *</label>
                    <input type="text" name="subject" class="form-control" required 
                           placeholder="Enter message subject...">
                </div>
                
                <div class="form-group">
                    <label>Message Type</label>
                    <select name="message_type" class="form-control">
                        <option value="general">General Message</option>
                        <option value="service_request">Service Request Related</option>
                        <option value="emergency">Emergency Alert</option>
                        <option value="schedule_change">Schedule Change</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_urgent" value="1">
                        Mark as Urgent
                    </label>
                    <small class="text-muted">(Recipients will be notified immediately)</small>
                </div>
                
                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" class="form-control" rows="8" 
                              placeholder="Type your message here..." required></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="send_message" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('composeModal')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Airport Management System - Communications</p>
        </div>
    </footer>
    
    <script>
        // Modal functions
        function openComposeModal() {
            document.getElementById('composeModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Mark message as read when clicked
        function markAsRead(messageId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'message_id';
            input.value = messageId;
            
            const submit = document.createElement('input');
            submit.type = 'hidden';
            submit.name = 'mark_read';
            submit.value = '1';
            
            form.appendChild(input);
            form.appendChild(submit);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Mark as read when viewing thread
        <?php if ($thread_id > 0 && isset($_GET['mark_read']) && $_GET['mark_read'] == 1): ?>
        // Already marked as read via URL parameter
        <?php elseif ($thread_id > 0): ?>
        // Offer to mark as read
        document.addEventListener('DOMContentLoaded', function() {
            if (confirm('Mark this thread as read?')) {
                markAsRead(<?php echo $thread_id; ?>);
            }
        });
        <?php endif; ?>
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Quick action buttons
        function archiveMessage(messageId) {
            if (confirm('Archive this message?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'message_id';
                input.value = messageId;
                
                const submit = document.createElement('input');
                submit.type = 'hidden';
                submit.name = 'archive_message';
                submit.value = '1';
                
                form.appendChild(input);
                form.appendChild(submit);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteMessage(messageId) {
            if (confirm('Delete this message?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'message_id';
                input.value = messageId;
                
                const submit = document.createElement('input');
                submit.type = 'hidden';
                submit.name = 'delete_message';
                submit.value = '1';
                
                form.appendChild(input);
                form.appendChild(submit);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>