<!DOCTYPE html>
<html>
    <head>
        <title>Simple BCS Docker Demo</title>

        <style>
            div {
                left: 50%;
                position: absolute; 
                margin-right: -50%; 
                transform: translate(-50%, -50%);
            }

            .hello {
                top:20%;
                font: 30px arial, sans-serif;"
            }

            .capside {
                top: 30%;
            }

            .bcs {
                top: 50%;
            }

            .info {
                top:70%;
                font: 20px arial, sans-serif;"
            }
        </style>
    </head>
    <body>
        <div class="hello">
            Hello world. Today is <?= date('l \t\h\e jS') ?>.
        </div>
        <div class="capside">
            <img src="capside.png">
        </div>
        <div class="bcs">
            <img src="bcs.png">
        </div>
        <div class="info">
            <?
                if(getenv('MY_NODE_NAME')) {
                    echo "Running on k8s node: " . getenv('MY_NODE_NAME') . "<br><br>";  
                }
                echo "Container: " . gethostname() . " / " . $_SERVER['SERVER_ADDR'];
            ?>
        </div>
    </body>
</html>