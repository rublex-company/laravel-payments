<!DOCTYPE html>
<html lang="en">

<!--

   This file is part of the Laravel Rublex Payments package.

   (c) Rublex Team <payments@rublex.io>

   For the full copyright and license information, please view the LICENSE
   file that was distributed with this source code.

-->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laravel Rublex Payment</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <link href="http://fontawesome.io/assets/font-awesome/css/font-awesome.css" rel="stylesheet" media="screen">

    <style>
        body {
            background-color: #dedede;
        }

        .topbar {
            background: #2A3F54;
            border-color: #2A3F54;
            border-radius: 0px;
        }

        .topbar .navbar-header a {
            color: #ffffff;
        }

        .wrapper {
            padding-left: 0px;
            -webkit-transition: all 0.5s ease;
            -moz-transition: all 0.5s ease;
            -o-transition: all 0.5s ease;
            transition: all 0.5s ease;
        }

        .main {
            width: 100%;
            position: relative;
            padding-bottom: 20px;
        }

        .content {
            margin-top: 70px;
            padding: 0 30px;
        }
    </style>

    <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>

</head>

<body>
<nav class="navbar navbar-default navbar-fixed-top topbar">
    <div class="container-fluid">

        <div class="navbar-header">

            <a href="#" class="navbar-brand">
                <span class="visible-xs">Laravel Rublex Payments</span>
                <span class="hidden-xs">Laravel Rublex Payments</span>
            </a>

            <p class="navbar-text">
                <a href="#" class="sidebar-toggle">
                    <i class="fa fa-bars"></i>
                </a>
            </p>

        </div>
    </div>
</nav>

<article class="wrapper">
    <section class="main">
        <section class="tab-content">
            <section class="tab-pane active fade in content" id="dashboard">
                <div class="row">
                    <div class="col-xs-6 col-sm-2">
                        <div class="panel panel-primary border">
                            <div class="panel-body">
                                <h5>API Status: <b>ON</b></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-xs-12 col-sm-3">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <b>API Log</b>
                            </div>
                            <ul class="list-group">
                                @foreach ($logs as $log)
                                    <li class="list-group-item">{{ $log->endpoint }} <span
                                                class="float-right badge badge-secondary">{{ $log->count }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="panel panel-default">
                            <div class="panel-heading">
                                What's New in Laravel Rublex Payments
                            </div>
                            <div class="panel-body">
                                Official site: <a href="https://pay.rublex.io" target="_blank">Payment Rublex</a><br>
                                Version: v{{ $version }} <br>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </section>
</article>

<script type="text/javascript">
    $(document).ready(function () {
        // DataTable
        $('#pList').DataTable()
    });
</script>
</body>

</html>
