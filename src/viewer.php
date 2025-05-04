<?php

declare(strict_types=1);

require_once('../vendor/autoload.php');


function the_top_errors(): void
{
    global $ventura_storage_provider;
    $errors = $ventura_storage_provider?->getTopErrors() ?? []; ?>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Type</th>
            <th>Message</th>
            <th>Updated</th>
            <th>Count</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($errors as $error) : ?>
            <tr>
                <td><?php echo htmlspecialchars($error['id'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($error['type'] ?? 'Unknown'); ?></td>
                <td>
                    <a href="?id=<?php echo htmlspecialchars($error['hash'] ?? ''); ?>"><?php echo htmlspecialchars($error['message'] ?? ''); ?></a>
                </td>
                <td><?php echo date('Y-m-d H:i:s', (int)($error['updated'] ?? 0)); ?></td>
                <td><?php echo htmlspecialchars($error['count'] ?? '0'); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function the_error(string $hash): void
{
    global $ventura_storage_provider;
    $error = $ventura_storage_provider?->getErrorById($hash) ?? [];
    ?>
    <table>
        <thead>
        <tr>
            <th>Field</th>
            <th>Value</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>ID</td>
            <td><?php echo htmlspecialchars($error['id'] ?? ''); ?></td>
        </tr>
        <tr>
            <td>Type</td>
            <td><?php echo htmlspecialchars($error['type'] ?? 'Unknown'); ?></td>
        </tr>
        <tr>
            <td>Message</td>
            <td><?php echo htmlspecialchars($error['message'] ?? ''); ?></td>
        </tr>
        <tr>
            <td>File</td>
            <td><?php echo htmlspecialchars($error['file'] ?? ''); ?></td>
        </tr>
        <tr>
            <td>Line</td>
            <td><?php echo htmlspecialchars($error['line'] ?? ''); ?></td>
        </tr>
        <tr>
            <td>Trace</td>
            <td>
                <pre><?php echo htmlspecialchars(print_r($error['trace'] ?? [], true)); ?></pre>
            </td>
        </tr>
        <tr>
            <td>Count</td>
            <td><?php echo htmlspecialchars($error['count'] ?? '0'); ?></td>
        </tr>
        <tr>
            <td>Updated</td>
            <td><?php echo date('Y-m-d H:i:s', (int)($error['updated'] ?? 0)); ?></td>
        </tr>
        <tr>
            <td>Context</td>
            <td>
                <?php if (!empty($error['context'])) : ?>
                    <div class="context-tabs">
                        <div class="tab-buttons">
                            <?php foreach ($error['context'] as $key => $value) : ?>
                                <button class="tab-button"
                                        onclick="showContext('<?php echo htmlspecialchars((string)$key); ?>')"><?php echo htmlspecialchars((string)$key); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <?php foreach ($error['context'] as $key => $value) : ?>
                            <div id="context-<?php echo htmlspecialchars((string)$key); ?>" class="context-content"
                                 style="display: none;">
                                <pre><?php echo htmlspecialchars(print_r($value, true)); ?></pre>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <script>
                        function showContext(key) {
                            document.querySelectorAll('.context-content').forEach(el => el.style.display = 'none');
                            document.querySelectorAll('.tab-button').forEach(el => el.classList.remove('active'));
                            document.getElementById('context-' + key).style.display = 'block';
                            document.querySelector(`button[onclick="showContext('${key}')"]`).classList.add('active');
                        }

                        // Show first context by default
                        document.querySelector('.tab-button')?.click();
                    </script>
                    <style>
                        .context-tabs {
                            border: 1px solid #ccc;
                            padding: 10px;
                        }

                        .tab-buttons {
                            margin-bottom: 10px;
                        }

                        .tab-button {
                            margin-right: 5px;
                            padding: 5px 10px;
                        }

                        .tab-button.active {
                            background: var(--bg);
                            color: var(--fg);
                        }

                        .context-content {
                            background: #f5f5f5;
                            padding: 10px;
                        }
                    </style>
                <?php else : ?>
                    <em>No context available</em>
                <?php endif; ?>
            </td>
        </tr>
        </tbody>
    </table>
    <?php
}

?>

<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="generator" content="Ventura">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ventura</title>
    <style>
        :root {
            --shadow: #050100;
            --bg: #9a5f3d;
            --fg: #fefefe;
            --bg2: #86745c;
            --bg-alt: #e2dcd7;
            --bg-alt2: #fefefe;
            --info: #fee684;
            --link: #027eab;
        }

        html, body {
            padding: 0;
            margin: 0;
        }

        blockquote {
            font-style: italic;
        }

        body {
            font: 16px/1.4em "Inter", sans-serif;
            color: var(--shadow);
            background: var(--bg-alt);
        }

        input[type='submit'], button {
            cursor: pointer;
        }

        main {
            margin: auto;
            padding: 1vw;

            table {
                margin: 0 auto;
                border: 1px solid var(--shadow)
            }

            tr:nth-child(even) {
                background-color: var(--bg-alt);
            }

            tr:nth-child(odd) {
                background-color: var(--bg-alt2);

            }
        }

        nav {
            background: var(--bg);
            color: var(--fg);
        }

        nav ul, nav li {
            display: inline;
            margin: 0;
            padding: 0;
        }

        nav a {
            display: inline-block;
            text-decoration: none;
            color: var(--fg);
            line-height: 2em;
            padding: 0 0.5em;
        }

        nav form {
            display: inline-block;
            margin: 0 0.5em;
            line-height: 1.9em;
        }

        nav ul {
            display: flow-root;
        }


        nav li.right {
            float: right;
        }

        footer {
            text-align: center;
        }


        form {
            margin: 2em 0;
        }


        h1, h2, h3 {
            line-height: 1.2em;
            font-weight: 800;
            color: var(--bg);
        }

        h1 {
            border-top: 2px solid var(--shadow);
            padding-top: 1em;
            font-weight: normal;
        }


        main a {
            color: var(--link);
        }

        main small {
            overflow: auto;
            border-top: 1px dotted var(--bg2);
            display: block;
            margin: 2em -2em 0;
            padding: 1px 2em;
        }

        main small a {
            margin-right: 1em;
        }

        small form {
            margin: auto;
            display: inline;
        }


        /* Text meant only for screen readers. */
        .screen-reader-text {
            border: 0;
            clip: rect(1px, 1px, 1px, 1px);
            clip-path: inset(50%);
            height: 1px;
            margin: -1px;
            overflow: hidden;
            padding: 0;
            position: absolute;
            width: 1px;
            word-wrap: normal !important;
        }

        .screen-reader-text:focus {
            background-color: #eee;
            clip: auto !important;
            clip-path: none;
            color: #444;
            display: block;
            font-size: 1em;
            height: auto;
            left: 5px;
            line-height: normal;
            padding: 15px 23px 14px;
            text-decoration: none;
            top: 5px;
            width: auto;
            z-index: 100000; /* Above WP toolbar. */
        }

    </style>
</head>
<body>
<nav>
    <ul>
        <li><a href="?">Ventura Viewer</a></li>
    </ul>
</nav>
<div class="container">
    <main>
        <?php
        if (empty($_GET['id'])) :
            the_top_errors();
        else :
            the_error($_GET['id']);
        endif;
        ?>


    </main>
</div>

<footer>
    <small>Powered by <a href="https://github.com/svandragt/ventura-bug-detective">Ventura</a>, bug detective.</small>
</footer>
</body>
</html>

