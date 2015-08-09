<!DOCTYPE html>
<meta lang="en" charset="utf-8">
<html>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <style>
        [type=radio]:checked ~ label {
            background: white;
            border-bottom: 1px solid white;
            z-index: 2;
        }

        [type=radio]:checked ~ label ~ .content {
            z-index: 1;
        }

        .graph {
            position: relative;
        }

        .graph > svg {
            position: relative;
            top: 0;
            right: 0;
        }

        .stats {
            position: absolute;
            right: 212px;
            top: 100px;
        }

        .stats th,
        .stats td {
            padding: 6px 10px;
            font-size: 0.8em;
        }

        #partition {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 10;
            top: 0;
            left: 0;
            background: #ddd;
            display: none;
        }

        #close-partition {
            display: none;
            position: absolute;
            z-index: 20;
            right: 15px;
            top: 15px;
            background: #f9373d;
            color: #fff;
            padding: 12px 15px;
        }

        #close-partition:hover {
            background: #D32F33;
            cursor: pointer;
        }

        #partition rect {
            stroke: #fff;
            fill: #aaa;
            fill-opacity: 1;
        }

        #partition rect.parent {
            cursor: pointer;
            fill: steelblue;
        }

        #partition text {
            pointer-events: none;
        }

        label {
            cursor: pointer;
        }
    </style>

    <title><?php echo $view->pageTitle(); ?></title>
</head>

<body>

<div class="container">
    <div class="page-header">
        <h1><?php echo $view->pageTitle(); ?></h1>
    </div>
    <?php echo $content ?>
</div>

<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

</body>
</html>
