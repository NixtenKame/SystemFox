<?php

function sendFriendRequest($db, $userId, $targetId) {
    if (!$userId || !$targetId || $userId == $targetId) {
        return "Invalid request.";
    }

    // --- (Pre-check logic omitted for brevity, but stays the same) ---
    // Check for existing relationship...
    
    // Use a transaction
    $db->begin_transaction();

    try {
        // 1. Insert ONLY the outgoing request (Sender -> Receiver)
        // This is the SINGLE record of the request.
        $stmt_out = $db->prepare("
            INSERT INTO user_relationships (user_id, target_id, relationship_status, created_at)
            VALUES (?, ?, 'pending_out', NOW())
            ON DUPLICATE KEY UPDATE relationship_status = 'pending_out', created_at = NOW()
        ");
        $stmt_out->bind_param("ii", $userId, $targetId);
        $stmt_out->execute();
        $stmt_out->close();

        // 2. NO SECOND INSERT. The 'pending_in' status is inferred by the receiver.

        // Notify the target ($targetId) that they have a new incoming request
        $notif_message = "You have a new friend request";
        $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
        $notif_stmt = $db->prepare($notif_query);
        $notif_stmt->bind_param("is", $targetId, $notif_message); 
        $notif_stmt->execute();
        $notif_stmt->close();

        $db->commit();
        return "Friend request sent!";

    } catch (Exception $e) {
        $db->rollback();
        error_log("Failed to send friend request: " . $e->getMessage());
        return "An error occurred while sending the request.";
    }
}


function acceptFriendRequest($db, $userId, $senderId) {
    if (!$userId || !$senderId || $userId == $senderId) {
        return "Invalid request.";
    }

    // Verify the incoming request exists
    $stmt = $db->prepare("
        SELECT relationship_status 
        FROM user_relationships 
        WHERE user_id = ? AND target_id = ? AND relationship_status = 'pending_in'
        LIMIT 1
    ");
    // $userId is the receiver, $senderId is the user who originally sent the request
    $stmt->bind_param("ii", $senderId, $userId); 
    $stmt->execute();
    $incoming = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$incoming) {
        return "No incoming request found.";
    }

    // Mark both sides as friends
    $stmt = $db->prepare("
        UPDATE user_relationships 
        SET relationship_status = 'friends' 
        WHERE (user_id = ? AND target_id = ? AND relationship_status = 'pending_out') 
           OR (user_id = ? AND target_id = ? AND relationship_status = 'pending_in')
    ");

    // Order: (Sender, Receiver) OR (Receiver, Sender)
    $stmt->bind_param("iiii", $senderId, $userId, $userId, $senderId);
    $stmt->execute();
    $stmt->close();
    
    $notif_message = "Your friend request was accepted";
    $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $notif_stmt = $db->prepare($notif_query);
    $notif_stmt->bind_param("is", $senderId, $notif_message); // Notify the original sender
    $notif_stmt->execute();
    $notif_stmt->close();

    return "Friend request accepted!";
}

function blockUser($db, $currentUserId, $targetId) {

    if ($currentUserId == $targetId) {
        return "You cannot block yourself.";
    }

    // Remove any existing relationship in BOTH directions
    $removeStmt = $db->prepare("
        DELETE FROM user_relationships
        WHERE (user_id = ? AND target_id = ?)
           OR (user_id = ? AND target_id = ?)
    ");
    $removeStmt->bind_param("iiii", $currentUserId, $targetId, $targetId, $currentUserId);
    $removeStmt->execute();
    $removeStmt->close();

    // Insert block record (current user → target)
    $stmt = $db->prepare("
        INSERT INTO user_relationships (user_id, target_id, relationship_status)
        VALUES (?, ?, 'blocked')
    ");
    $stmt->bind_param("ii", $currentUserId, $targetId);
    $stmt->execute();
    $stmt->close();

    // Insert reverse block record (target → user)
    $stmt2 = $db->prepare("
        INSERT INTO user_relationships (user_id, target_id, relationship_status)
        VALUES (?, ?, 'blocked_by')
    ");
    $stmt2->bind_param("ii", $targetId, $currentUserId);
    $stmt2->execute();
    $stmt2->close();

    $notif_message = "You are no longer friends with user $targetId";
    // Assuming you want to notify the user who was blocked, which is $targetId
    $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $notif_stmt = $db->prepare($notif_query);
    $notif_stmt->bind_param("is", $targetId, $notif_message);
    $notif_stmt->execute();
    $notif_stmt->close();

    return "User has been blocked.";
}