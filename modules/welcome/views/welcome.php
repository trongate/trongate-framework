<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trongate</title>
</head>
<body>
<section>
    <h1>It Totally Works!</h1>
    <p>Welcome to Trongate - the revolutionary new framework for developers who love pure PHP.</p>

    <p>To explore the documentation go to <a href="https://trongate.io/docs" target="_blank">https://trongate.io/docs</a></p>

    <p><span class="blink"><a href="https://github.com/trongate/trongate-framework" target="_blank">Please give Trongate a star on GitHub</a>.</span> If Trongate becomes a top ten PHP framework, it'll be the most electrifying event in the history of web development!  Join the revolution!  Be part of something amazing.  Together, we <i><span style="color: white">SHALL</span></i> make PHP great again!</p>

</section>    
    <style>
        body {
            font-size: 2em;
            background: #636ec6;
            color: #ddd;
            text-align: center;
            font-family: "Lucida Console", Monaco, monospace;
        }

        section {
            max-width: 1200px;
            margin:  0 auto;
        }

        section p {
            line-height: 1.3em;
        }

        h1 {
            margin-top: 2em;
        }

        h1, h2 {
            text-transform: uppercase;
        }

        a { color: white; }

        .blink{
            text-decoration: blink;
            -webkit-animation-name: blinker;
            -webkit-animation-duration: 0.5s;
            -webkit-animation-iteration-count:infinite;
            -webkit-animation-timing-function:ease-in-out;
            -webkit-animation-direction: alternate;
        }

        @-webkit-keyframes blinker {
          from {opacity: 1.0;}
          to {opacity: 0.0;}
        }

        .blink a:hover {
            color: yellow;
        }

    </style>
</body>
</html>