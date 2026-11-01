<?php
/**
 * WhatsApp Blood Donor Broadcast API Handler
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

$REQUIRED_PIN = '7530';

$configFile = __DIR__ . '/config.json';
$donorsFile = __DIR__ . '/donors.json';
$historyFile = __DIR__ . '/history.json';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle PIN verification
if ($action === 'verify_pin') {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = isset($data['pin']) ? trim($data['pin']) : '';
    if ($pin === $REQUIRED_PIN) {
        $_SESSION['authenticated'] = true;
        echo json_encode(['status' => 'success', 'message' => 'PIN সঠিক হয়েছে']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ভুল PIN দিয়েছেন!']);
    }
    exit;
}

// Handle Logout
if ($action === 'logout') {
    unset($_SESSION['authenticated']);
    session_destroy();
    echo json_encode(['status' => 'success']);
    exit;
}

// Authentication Check
if (empty($_SESSION['authenticated'])) {
    http_response_code(401);
    echo json_encode(['status' => 'unauthorized', 'message' => 'অনুগ্রহ করে PIN দিয়ে প্রবেশ করুন।']);
    exit;
}

// Default configuration
$config = [
    'evolution_url' => 'http://127.0.0.1:18080',
    'api_key' => 'EvoApiKeySecure2026!#',
    'instance_name' => 'DhakaOffice',
    'sender_phone' => '',
    'min_delay' => 6,
    'max_delay' => 12
];

if (file_exists($configFile)) {
    $savedConfig = json_decode(file_get_contents($configFile), true);
    if (is_array($savedConfig)) {
        $config = array_merge($config, $savedConfig);
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. Fetch Donors
if ($action === 'get_donors') {
    $donors = file_exists($donorsFile) ? json_decode(file_get_contents($donorsFile), true) : [];
    $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
    
    echo json_encode([
        'status' => 'success',
        'donors' => $donors,
        'history' => $history,
        'config' => [
            'evolution_url' => $config['evolution_url'],
            'instance_name' => $config['instance_name'],
            'sender_phone' => $config['sender_phone'],
            'min_delay' => $config['min_delay'],
            'max_delay' => $config['max_delay']
        ]
    ]);
    exit;
}

// 1.1 Import Donors CSV
if ($action === 'import_csv') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'CSV ফাইল পাওয়া যায়নি বা আপলোডে সমস্যা হয়েছে।']);
        exit;
    }

    $fileTmp = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($fileTmp, 'r');
    if (!$handle) {
        echo json_encode(['status' => 'error', 'message' => 'CSV ফাইল পড়তে ব্যর্থ।']);
        exit;
    }

    $existingDonors = file_exists($donorsFile) ? json_decode(file_get_contents($donorsFile), true) : [];
    // Normalize and map all existing numbers (last 10 digits check)
    $existingPhones = [];
    foreach ($existingDonors as $d) {
        $clean = preg_replace('/[^0-9]/', '', $d['phone']);
        $last10 = substr($clean, -10);
        if (!empty($last10)) {
            $existingPhones[$last10] = true;
        }
    }

    $header = fgetcsv($handle);
    $imported = 0;
    $skipped = 0;
    $duplicates = 0;

    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row) || count($row) < 2) continue;
        
        // Expected columns: Name, Phone, Location, Site, Last Donation
        $name = isset($row[0]) && !empty(trim($row[0])) ? trim($row[0]) : 'ডোনার';
        $phoneRaw = isset($row[1]) ? trim($row[1]) : '';
        $location = isset($row[2]) ? trim($row[2]) : '';
        $site = isset($row[3]) && !empty(trim($row[3])) ? trim($row[3]) : 'custom-import';
        $lastDonation = isset($row[4]) ? trim($row[4]) : '';

        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneRaw);
        if (strlen($cleanPhone) < 10) {
            $skipped++;
            continue;
        }

        // Compare using last 10 digits (matches 017..., 88017..., +88017...)
        $last10 = substr($cleanPhone, -10);

        // Strict duplicate check against existing database & current CSV batch
        if (isset($existingPhones[$last10])) {
            $duplicates++;
            continue;
        }

        $standardPhone = '0' . $last10;

        $existingDonors[] = [
            'site' => $site,
            'name' => $name,
            'phone' => $standardPhone,
            'location' => $location,
            'last_donation' => $lastDonation
        ];
        
        // Mark as seen so duplicates inside the same CSV are also skipped
        $existingPhones[$last10] = true;
        $imported++;
    }
    fclose($handle);

    file_put_contents($donorsFile, json_encode($existingDonors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo json_encode([
        'status' => 'success',
        'imported' => $imported,
        'duplicates' => $duplicates,
        'skipped' => $skipped,
        'total' => count($existingDonors),
        'message' => "সফলভাবে {$imported} জন নতুন ডোনার যুক্ত হয়েছে। [ডুপ্লিকেট বাদ: {$duplicates} টি, ইনভ্যালিড নম্বর বাদ: {$skipped} টি]"
    ]);
    exit;
}

// 2. Save Config
if ($action === 'save_config') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['evolution_url'])) {
        $config['evolution_url'] = rtrim(trim($data['evolution_url']), '/');
    }
    if (!empty($data['instance_name'])) {
        $config['instance_name'] = trim($data['instance_name']);
    }
    if (isset($data['api_key'])) {
        $config['api_key'] = trim($data['api_key']);
    }
    if (!empty($data['sender_phone'])) {
        $config['sender_phone'] = trim($data['sender_phone']);
    }
    if (isset($data['min_delay'])) {
        $config['min_delay'] = max(3, intval($data['min_delay']));
    }
    if (isset($data['max_delay'])) {
        $config['max_delay'] = max($config['min_delay'], intval($data['max_delay']));
    }
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    echo json_encode(['status' => 'success', 'message' => 'সেটিংস সেভ হয়েছে।']);
    exit;
}

// 3. Fetch Evolution Instances
if ($action === 'get_instances') {
    $url = rtrim($config['evolution_url'], '/') . '/instance/fetchInstances';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $config['api_key'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $instances = json_decode($response, true);
        echo json_encode(['status' => 'success', 'instances' => $instances]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Evolution API কানেক্ট করা যায়নি (HTTP ' . $httpCode . ')']);
    }
    exit;
}

// 4. Check WhatsApp on Phone Number
if ($action === 'check_whatsapp') {
    $data = json_decode(file_get_contents('php://input'), true);
    $phone = isset($data['phone']) ? preg_replace('/[^0-9]/', '', $data['phone']) : '';
    
    if (strlen($phone) === 11 && substr($phone, 0, 2) === '01') {
        $phone = '88' . $phone;
    }

    if (empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'ফোন নম্বর পাওয়া যায়নি']);
        exit;
    }

    $instance = $config['instance_name'];
    $url = rtrim($config['evolution_url'], '/') . '/chat/whatsappNumbers/' . urlencode($instance);
    
    $payload = json_encode(['numbers' => [$phone]]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $config['api_key'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $exists = false;
    $jid = '';
    $validResponse = false;

    if ($code === 200 && $res) {
        $resData = json_decode($res, true);
        if (is_array($resData) && isset($resData[0]['exists'])) {
            $exists = (bool)$resData[0]['exists'];
            $jid = isset($resData[0]['jid']) ? $resData[0]['jid'] : '';
            $validResponse = true;
        }
    }

    // Only mark as nowhatsapp if the API call was 100% valid (HTTP 200) and explicitly returned false
    if ($validResponse && !$exists) {
        $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
        $history[$phone] = [
            'sent_at' => date('Y-m-d H:i:s'),
            'status' => 'nowhatsapp',
            'donor_name' => isset($data['name']) ? $data['name'] : '',
            'site' => isset($data['site']) ? $data['site'] : '',
            'http_code' => 200
        ];
        file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));
    }

    if (!$validResponse) {
        echo json_encode([
            'status' => 'error',
            'message' => 'WhatsApp Instance কানেক্টেড নেই বা রেসপন্স দেয়নি (HTTP ' . $code . ')',
            'phone' => $phone,
            'raw' => $res
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'exists' => $exists,
        'phone' => $phone,
        'jid' => $jid,
        'raw' => $res
    ]);
    exit;
}

// 5. Send Message to Single Donor
if ($action === 'send_message') {
    $data = json_decode(file_get_contents('php://input'), true);
    $phone = isset($data['phone']) ? preg_replace('/[^0-9]/', '', $data['phone']) : '';
    $message = isset($data['message']) ? $data['message'] : '';
    $donorName = isset($data['name']) ? $data['name'] : '';
    $site = isset($data['site']) ? $data['site'] : '';

    if (strlen($phone) === 11 && substr($phone, 0, 2) === '01') {
        $phone = '88' . $phone;
    }

    if (empty($phone) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'নাম্বার অথবা মেসেজ খালি']);
        exit;
    }

    $instance = $config['instance_name'];
    $url = rtrim($config['evolution_url'], '/') . '/message/sendText/' . urlencode($instance);

    $payload = json_encode([
        'number' => $phone,
        'text' => $message,
        'delay' => 1200,
        'linkPreview' => false
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $config['api_key'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $success = ($code === 200 || $code === 201);
    
    // Log history
    $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
    $history[$phone] = [
        'sent_at' => date('Y-m-d H:i:s'),
        'status' => $success ? 'sent' : 'failed',
        'donor_name' => $donorName,
        'site' => $site,
        'http_code' => $code
    ];
    file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));

    echo json_encode([
        'status' => $success ? 'success' : 'error',
        'code' => $code,
        'response' => json_decode($res, true) ?: $res
    ]);
    exit;
}

// 6. Manual Status Toggle / Update
if ($action === 'mark_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    $phone = isset($data['phone']) ? preg_replace('/[^0-9]/', '', $data['phone']) : '';
    $status = isset($data['status']) ? $data['status'] : 'sent';
    $donorName = isset($data['name']) ? $data['name'] : '';
    $site = isset($data['site']) ? $data['site'] : '';

    if (strlen($phone) === 11 && substr($phone, 0, 2) === '01') {
        $phone = '88' . $phone;
    }

    if (empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'ফোন নম্বর খালি']);
        exit;
    }

    $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
    
    if ($status === 'pending') {
        unset($history[$phone]);
    } else {
        $history[$phone] = [
            'sent_at' => date('Y-m-d H:i:s'),
            'status' => $status,
            'donor_name' => $donorName,
            'site' => $site,
            'http_code' => 'manual'
        ];
    }
    file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));

    echo json_encode(['status' => 'success', 'marked_status' => $status]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
