<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Southbacon</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Helvetica', sans-serif;
                height: 100vh;
                margin: 0;
            }

            .title {
                font-size: 40px;
                font-family: 'Raleway', sans-serif;
                font-weight: 100;
                margin: 40px 0;
                text-align: center;
            }

            .content {
                display: flex;
                max-width: 800px;
                margin: 0 auto;
            }

            .users, .things {
                width: 50%;
                padding: 0 5px;
            }

            ul {
                list-style-type: none;
                padding: 0;
                width: 100%;
            }

            ul li {
                display: flex;
                padding: 5px;
            }

            ul li:nth-child(2n) {
                background-color: #F7F7F7;
            }

            .name {
                width: 80%;
            }

            .karma {
                width: 20%;
                text-align: right;
            }

            .list-title {
                font-size: 30px;
                font-family: 'Raleway', sans-serif;
                font-weight: 100;
                margin: 20px 0;
                text-align: center;
            }

        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">

            <div class="title">Southbacon Leaderboard</div>

            <div class="content">

                <div class="users">
                    <div class="list-title">Users</div>
                    <ul>
                        @foreach ($users as $user)
                            <li><span class="name">&#64;{{ $user->name }}</span> <span class="karma">{{ $user->karma }}</span></li>
                        @endforeach
                    </ul>
                </div>

                <div class="things">
                    <div class="list-title">Things</div>
                    <ul>
                        @foreach ($things as $thing)
                            <li><span class="name">&#64;{{ $thing->name }}</span> <span class="karma">{{ $thing->karma }}</span></li>
                        @endforeach
                    </ul>
                </div>

            </div>
        </div>
    </body>
</html>
