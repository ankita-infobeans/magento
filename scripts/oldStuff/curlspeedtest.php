<html>
    <head>
        <style type="text/css">
        table.sample {
                border-width: 1px;
                border-spacing: 0px;
                border-style: solid;
                border-color: gray;
                border-collapse: collapse;
                background-color: white;
        }
        table.sample th {
                border-width: 1px;
                padding: 5px;
                border-style: solid;
                border-color: gray;
                background-color: white;
        }
        table.sample td {
                border-width: 1px;
                padding: 5px;
                border-style: solid;
                border-color: gray;
                background-color: white;
        }
        </style>
    </head>
    <body>
        <h1>php curl speed test</h1>
        <form action="curlspeedtest.php" method="post">
            <input type="text" name="url" />
            <input type="submit" value="Go" />
        </form>
        <br><br>
        <?php if (isset($_POST['url'])): ?>
            <?php    
                $url = $_POST['url'];
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $start = microtime(true);
                curl_exec($ch);
                $end = microtime(true);
            ?>
            curl internal info:<br>
            <table class="sample">
            <?php foreach (curl_getinfo($ch) as $name => $value): ?>
                <tr>
                    <td><?php echo $name; ?></td>
                    <td><?php echo $value; ?></td>
                </tr>
            <?php endforeach; ?>
            </table>
            <?php curl_close($ch); ?>
        <?php endif; ?>
    </body>
</html>