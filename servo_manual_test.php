<?php
declare(strict_types=1);

const SERIAL_PORT = 'COM4';
const SERIAL_BAUD = 9600;
const DEFAULT_DURATION_MS = 3000;

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function readSerialLines($handle, float $timeoutSeconds): array
{
    $lines = [];
    $buffer = '';
    $deadline = microtime(true) + $timeoutSeconds;

    stream_set_blocking($handle, false);
    while (microtime(true) < $deadline) {
        $chunk = fread($handle, 256);
        if (is_string($chunk) && $chunk !== '') {
            $buffer .= $chunk;
            while (($newline = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $newline));
                $buffer = substr($buffer, $newline + 1);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }
        usleep(20000);
    }

    $remaining = trim($buffer);
    if ($remaining !== '') {
        $lines[] = $remaining;
    }
    return $lines;
}

function sendCommand($handle, string $command): array
{
    $written = fwrite($handle, $command . "\n");
    fflush($handle);
    if ($written === false || $written < strlen($command) + 1) {
        throw new RuntimeException("Could not write {$command} to the serial port.");
    }
    return readSerialLines($handle, 2.5);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $duration = filter_input(INPUT_POST, 'duration_ms', FILTER_VALIDATE_INT);
    if ($duration === false || $duration === null || $duration < 100 || $duration > 10000) {
        jsonResponse(['success' => false, 'message' => 'Duration must be between 100 and 10000 milliseconds.'], 422);
    }

    // Configure the Windows serial port. Values are constants, not user input.
    $modeOutput = [];
    $modeCode = 1;
    exec('mode ' . SERIAL_PORT . ': BAUD=' . SERIAL_BAUD . ' PARITY=n DATA=8 STOP=1', $modeOutput, $modeCode);
    if ($modeCode !== 0) {
        jsonResponse([
            'success' => false,
            'message' => SERIAL_PORT . ' could not be configured. Check the connection and close Serial Monitor or any application using the port.',
            'details' => $modeOutput
        ], 503);
    }

    $device = '\\\\.\\' . SERIAL_PORT;
    $handle = @fopen($device, 'r+b');
    if ($handle === false) {
        jsonResponse([
            'success' => false,
            'message' => 'Could not open ' . SERIAL_PORT . '. The port may be busy or unavailable.'
        ], 503);
    }

    try {
        // Some boards reset when their serial port opens.
        usleep(1800000);
        readSerialLines($handle, 0.3);

        $durationCommand = 'SET_DURATION:' . $duration;
        $durationReplies = sendCommand($handle, $durationCommand);
        $durationAcknowledged = count(array_filter(
            $durationReplies,
            static fn(string $line): bool => stripos($line, 'ACK') !== false || stripos($line, 'OK') !== false
        )) > 0;

        if (!$durationAcknowledged) {
            $responsePayload = [
                'success' => false,
                'message' => "Arduino did not acknowledge {$durationCommand}.",
                'sent' => [$durationCommand],
                'replies' => $durationReplies
            ];
            $responseStatus = 504;
        } else {
            $feedReplies = sendCommand($handle, 'FEED_NOW');
            $feedRejected = count(array_filter(
                $feedReplies,
                static fn(string $line): bool => stripos($line, 'ERR') !== false
            )) > 0;

            $responsePayload = [
                'success' => !$feedRejected,
                'message' => $feedRejected ? 'Arduino rejected the feed command.' : 'Manual feed command sent successfully.',
                'sent' => [$durationCommand, 'FEED_NOW'],
                'replies' => array_merge($durationReplies, $feedReplies)
            ];
            $responseStatus = $feedRejected ? 409 : 200;
        }
    } catch (Throwable $error) {
        $responsePayload = ['success' => false, 'message' => $error->getMessage()];
        $responseStatus = 500;
    } finally {
        fclose($handle);
    }
    jsonResponse($responsePayload, $responseStatus);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Servo Manual Feed Test</title>
  <style>
    *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#eef4f0;font-family:Arial,sans-serif;color:#10261f}.card{width:min(520px,calc(100% - 28px));background:#fff;border:1px solid #dce5df;border-radius:20px;padding:30px;box-shadow:0 14px 45px rgba(16,38,31,.09)}h1{text-align:center;margin:0 0 8px;font-size:25px}p{text-align:center;color:#64736d}.status{margin:22px 0;padding:14px;border-radius:10px;background:#f1f6f3;text-align:center}.status.busy{color:#735c13;background:#fff7d6}.status.ok{color:#116339;background:#dcfce7}.status.error{color:#991b1b;background:#fee2e2}label{display:grid;gap:7px;margin-bottom:16px;font-weight:700;font-size:13px}input{padding:11px;border:1px solid #b9c8c0;border-radius:8px;font:inherit}button{width:100%;border:0;border-radius:9px;padding:13px;background:#197653;color:#fff;font-weight:800;font-size:15px;cursor:pointer}button:disabled{opacity:.55;cursor:wait}pre{display:none;margin:18px 0 0;padding:14px;border-radius:9px;background:#12231d;color:#d7f4e6;white-space:pre-wrap;overflow-wrap:anywhere;font-size:12px}.note{font-size:11px;margin-top:12px}
  </style>
</head>
<body>
  <main class="card">
    <h1>Manual Servo Feed Test</h1>
    <p>Serial port <?= htmlspecialchars(SERIAL_PORT) ?> at <?= SERIAL_BAUD ?> baud</p>
    <div class="status" id="status">Ready to send a test command</div>
    <form id="feedForm">
      <label>Servo open duration (milliseconds)
        <input type="number" name="duration_ms" value="<?= DEFAULT_DURATION_MS ?>" min="100" max="10000" required>
      </label>
      <button type="submit" id="feedButton">Feed Now</button>
    </form>
    <pre id="serialLog"></pre>
    <p class="note">Close Arduino Serial Monitor and other feeder programs first; only one program can use COM4 at a time.</p>
  </main>
  <script>
    const form = document.getElementById('feedForm');
    const button = document.getElementById('feedButton');
    const statusBox = document.getElementById('status');
    const log = document.getElementById('serialLog');

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!confirm('Run one manual feed test now? Keep hands clear of the servo mechanism.')) return;
      button.disabled = true;
      statusBox.className = 'status busy';
      statusBox.textContent = 'Connecting to COM4 and waiting for Arduino...';
      log.style.display = 'none';
      try {
        const response = await fetch(location.href, {method:'POST', body:new FormData(form)});
        const data = await response.json();
        statusBox.className = `status ${data.success ? 'ok' : 'error'}`;
        statusBox.textContent = data.message || 'Test completed.';
        log.textContent = [
          ...(data.sent || []).map(value => `Sent: ${value}`),
          ...(data.replies || []).map(value => `Arduino: ${value}`),
          ...((data.details || []).map(value => `System: ${value}`))
        ].join('\n') || 'No serial response received.';
        log.style.display = 'block';
      } catch (error) {
        statusBox.className = 'status error';
        statusBox.textContent = 'The web test could not communicate with the PHP endpoint.';
      } finally {
        button.disabled = false;
      }
    });
  </script>
</body>
</html>
