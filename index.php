<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $board = $_POST['board'];
    $playerMove = $_POST['move'];
    $llmEndpoint = 'https://172.23.144.22/api/v1/chat/completions'; // Replace with your LLM API endpoint
    $apiKey = 'ccd7a87c-70cd-419c-b1b0-a0ddd0583415';

    // Convert the board array to a format suitable for the LLM API
    $payload = json_encode([
        'board' => $board,
        'move' => $playerMove
    ]);

    // Call the LLM API
    $ch = curl_init($llmEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey, // Add the Authorization header
        'accept: application/json' 
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        echo json_encode(['error' => 'Failed to contact the LLM endpoint.']);
        exit;
    }

    // Decode LLM's response
    $llmResponse = json_decode($response, true);
    echo json_encode($llmResponse); // Send back to the frontend
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tic Tac Toe with LLM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        .board {
            display: grid;
            grid-template-columns: repeat(3, 100px);
            gap: 5px;
            justify-content: center;
        }
        .cell {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #333;
            font-size: 24px;
            cursor: pointer;
        }
        .cell.disabled {
            cursor: not-allowed;
            background-color: #f0f0f0;
        }
        #status {
            margin-top: 20px;
        }
    </style>
    <script>
        let board = ['', '', '', '', '', '', '', '', ''];
        let isGameOver = false;

        function renderBoard() {
            const boardContainer = document.getElementById('board');
            boardContainer.innerHTML = '';
            board.forEach((cell, index) => {
                const cellElement = document.createElement('div');
                cellElement.className = 'cell';
                if (cell) cellElement.classList.add('disabled');
                cellElement.textContent = cell;
                cellElement.addEventListener('click', () => makeMove(index));
                boardContainer.appendChild(cellElement);
            });
        }

        function makeMove(index) {
            if (isGameOver || board[index]) return;
            board[index] = 'X'; // Player's move
            renderBoard();
            updateStatus('Thinking...');
            fetchLLMMove();
        }

        function fetchLLMMove() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ board, move: 'X' }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        updateStatus(data.error);
                        return;
                    }

                    board = data.board; // Update the board with the LLM's response
                    renderBoard();
                    if (data.gameOver) {
                        isGameOver = true;
                        updateStatus(data.message);
                    } else {
                        updateStatus('Your turn!');
                    }
                })
                .catch(error => {
                    console.error(error);
                    updateStatus('An error occurred. Please try again.');
                });
        }

        function updateStatus(message) {
            document.getElementById('status').textContent = message;
        }

        window.onload = () => {
            renderBoard();
            updateStatus('Your turn!');
        };
    </script>
</head>
<body>
    <h1>Tic Tac Toe</h1>
    <div id="board" class="board"></div>
    <div id="status"></div>
</body>
</html>
