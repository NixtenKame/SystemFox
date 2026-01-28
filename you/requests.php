<?php
/**
 * Friend Requests Management Page - requests
 * NOW using a SINGLE database record per pending request.
 */

// Define Root Path and Include Configuration
define('ROOT_PATH', realpath(__DIR__ . '/../..'));
include_once ROOT_PATH . '/connections/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

$userId = $_SESSION['user_id'];

// -----------------------------------------------------------
// 2. Handle POST Friend Request Actions
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Security: CSRF Check
    if (function_exists('csrf_check') && !csrf_check()) {
        error_log("CSRF token mismatch for User ID: {$userId}");
        header("Location: /you/requests?error=csrf_failed");
        exit;
    }

    $db->begin_transaction(); 

    try {
        if (isset($_POST['accept'])) {
            // $requesterId is the ID of the user who SENT the request
            $requesterId = intval($_POST['receiver_id']); 
            
            // UPDATE the SINGLE record from 'pending_out' to 'friends'
            $stmt = $db->prepare("UPDATE user_relationships SET relationship_status = 'friends' WHERE user_id = ? AND target_id = ? AND relationship_status = 'pending_out'");
            // Binds: (Requester ID, Receiver ID)
            $stmt->bind_param("ii", $requesterId, $userId);
            $stmt->execute();
            $stmt->close();
            // NOTE: In this single-record model, you must insert a second 'friends' record if you want the other user to see the friendship status when querying user_id = $userId

        } elseif (isset($_POST['decline'])) {
            // $requesterId is the ID of the user who SENT the request
            $requesterId = intval($_POST['receiver_id']); 
            
            // DELETE the single request record
            $stmt = $db->prepare("DELETE FROM user_relationships WHERE user_id = ? AND target_id = ? AND relationship_status = 'pending_out'");
            // Binds: (Requester ID, Receiver ID)
            $stmt->bind_param("ii", $requesterId, $userId);
            $stmt->execute();
            $stmt->close();

        } elseif (isset($_POST['cancel'])) {
            // $targetId is the ID of the user receiving the cancellation (the original target)
            $targetId = intval($_POST['requester_id']); 
            
            // DELETE the single request record
            $stmt = $db->prepare("DELETE FROM user_relationships WHERE user_id = ? AND target_id = ? AND relationship_status = 'pending_out'");
            // Binds: (Sender ID, Target ID)
            $stmt->bind_param("ii", $userId, $targetId);
            $stmt->execute();
            $stmt->close();
        }
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        error_log("Friend request action failed: " . $e->getMessage());
        header("Location: /you/requests?error=db_failed");
        exit;
    }

    // Redirect
    header("Location: /you/requests");
    exit;
}

// -----------------------------------------------------------
// 3. Data Retrieval
// -----------------------------------------------------------

// --- A. Get Incoming Friend Requests (You are the target/receiver) ---
$incoming = [];
$stmt = $db->prepare("
    SELECT 
        ur.id as request_id,
        ur.user_id AS requester_id,
        ur.target_id AS receiver_id,
        ur.created_at,
        u.username
    FROM user_relationships ur
    JOIN users u ON ur.user_id = u.id              -- Join on the user who SENT the request (ur.user_id)
    WHERE ur.target_id = ? AND ur.relationship_status = 'pending_out' -- Logged-in user is the target (ur.target_id)
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $incoming[] = $row;
}
$stmt->close();

// --- B. Get Outgoing Friend Requests (You are the sender/requester) ---
$outgoing = [];
$stmt2 = $db->prepare("
    SELECT 
        ur.id as request_id,
        ur.user_id AS requester_id,
        ur.target_id AS receiver_id,
        ur.created_at,
        u.username
    FROM user_relationships ur
    JOIN users u ON ur.target_id = u.id             -- Join on the user who is the RECEIVER (ur.target_id)
    WHERE ur.user_id = ? AND ur.relationship_status = 'pending_out'  -- Logged-in user is the sender (ur.user_id)
");
$stmt2->bind_param("i", $userId);
$stmt2->execute();
$result2 = $stmt2->get_result();
while ($row = $result2->fetch_assoc()) {
    $outgoing[] = $row;
}
$stmt2->close();

include('../includes/header.php')
?>
<!DOCTYPE html>
<html>
<head>
    <title>Friend Requests</title>
<style>
    /* --- Default (Light) Theme --- */
    body { 
        color: #333; 
        background-color: #fff; 
        font-family: Arial, sans-serif; /* Added for better readability */
    }
    table { border-collapse: collapse; width: 100%; max-width: 600px; margin: 20px 0; }
    td, th { padding: 10px; border: 1px solid #ccc; text-align: left; }
    th { background: #f0f0f0; }
    button { padding: 8px 16px; margin-right: 8px; cursor: pointer; border: 1px solid #ccc; background: #fff; color: #333; }
    
    /* Action-specific light styles */
    button[name="accept"] { background: #d4edda; border-color: #c3e6cb; color: #155724; }
    button[name="decline"] { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    button[name="cancel"] { background: #f3f3f3; border-color: #ccc; color: #333; }
    .request-section { margin-bottom: 40px; }


    /* --- Dark Theme Overrides (When body has the 'dark' class) --- */
    body.dark {
        color: #e0e0e0; /* Light text color */
        background-color: #1e1e1e; /* Dark background color */
    }

    /* General element dark styling */
    body.dark h2 { color: #ffffff; } /* Ensure headings are bright */
    body.dark td, 
    body.dark th {
        border-color: #444; /* Darker borders */
        background-color: #252526; /* Slightly lighter surface for contrast */
    }
    body.dark th {
        background: #333333; /* Darker header background */
        color: #ffffff;
    }
    body.dark table {
        border-color: #444;
    }

    /* Button dark styling (general appearance) */
    body.dark button {
        background: #333;
        color: #e0e0e0;
        border-color: #555;
    }
    
    /* Action-specific dark styles */
    body.dark button[name="accept"] { 
        background: #104e28; /* Dark green background */
        border-color: #2b704c; 
        color: #d4edda; /* Light green text */
    }
    body.dark button[name="decline"] { 
        background: #641e24; /* Dark red background */
        border-color: #923a41; 
        color: #f8d7da; /* Light red text */
    }
    body.dark button[name="cancel"] { 
        background: #444; /* Neutral dark background */
        border-color: #666; 
        color: #e0e0e0; 
    }
</style>
    <link rel="stylesheet" href="/public/css/styles.css" />
</head>
<body>
    <nav>
<?php include_once '../includes/nav.php'; ?>
</nav>
<?php include_once '../includes/site-notice.php'; ?>
    <div class="request-section">
        <h2><i class="fa-solid fa-inbox"></i> Incoming Friend Requests</h2>
        <?php if (count($incoming)): ?>
        <table>
            <tr>
                <th>User (Requester)</th>
                <th>Requested At</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($incoming as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['username']) ?></td>
                    <td><?= htmlspecialchars($req['created_at']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <?php if (function_exists('csrf_input')) echo csrf_input(); ?>
                            <input type="hidden" name="receiver_id" value="<?= htmlspecialchars($req['requester_id']) ?>">
                            <button name="accept" type="submit">Accept</button>
                            <button name="decline" type="submit">Decline</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
            <p>No incoming friend requests.</p>
        <?php endif; ?>
    </div>

    <hr>

    <div class="request-section">
        <h2><i class="fa-solid fa-inbox"></i> Sent Friend Requests</h2>
        <?php if (count($outgoing)): ?>
        <table>
            <tr>
                <th>User (Receiver)</th>
                <th>Requested At</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($outgoing as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['username']) ?></td>
                    <td><?= htmlspecialchars($req['created_at']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <?php if (function_exists('csrf_input')) echo csrf_input(); ?>
                            <input type="hidden" name="requester_id" value="<?= htmlspecialchars($req['receiver_id']) ?>">
                            <button name="cancel" type="submit">Cancel</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
            <p>No outgoing friend requests.</p>
        <?php endif; ?>
    </div>
<?php include('../includes/version.php'); ?>
<footer>
    <p>&copy; 2026 <a href="https://github.com/NixtenKame/SystemFox/">FluffFox.</a> (nixten.ddns.net) All Rights Reserved. 
        <a class="link" href="/assets/docs/version"><?php echo htmlspecialchars($version); ?></a>
    </p>
</footer>
</body>
</html>