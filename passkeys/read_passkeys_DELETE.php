<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('user_profile')) {
    header("Location:../../index.php?msg1");
    exit();
}

if(isset($_SESSION['id'])) {
    $userId = $_SESSION['id'];
    $stmt = $dbc->prepare("SELECT * FROM passkeys WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $passkeys = $result->fetch_all(MYSQLI_ASSOC);

    $data = '<table class="table table-sm table-hover">
                <thead class="table-dark text-center">
                    <tr>
						<th>Session</th>
                        <th>Count</th>
                        <th>Device</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody>';

    if (count($passkeys) > 0) {
        foreach ($passkeys as $passkey) {
			$passkeyId = htmlspecialchars(strip_tags($passkey['id']));
			$userID = htmlspecialchars(strip_tags($passkey['user_id']));
            $signCount = htmlspecialchars(strip_tags($passkey['sign_count']));
            $userAgent = 'User Agent Placeholder';
            
			$data .= '<tr data-id="' . $passkeyId . '">
						<td class="text-center align-middle">' . $userID . '</td>
                        <td class="text-center align-middle">' . $signCount . '</td>
                        <td class="text-center align-middle">' . $userAgent . '</td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-sm btn-dark" onclick="deletePasskey(' . $passkeyId . ')">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>';
        }
    } else {
        $data .= '<tr><td colspan="4" class="text-center">No passkeys registered.</td></tr>';
    }

    $data .= '</tbody></table>';

    echo $data;

    mysqli_stmt_close($stmt);
}

mysqli_close($dbc);
?>