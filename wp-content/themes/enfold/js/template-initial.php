<?php
/* ------------------------------------------------------------------------- *
 * Template name: Init Page
/* ------------------------------------------------------------------------- */
?>

<!DOCTYPE html>
<html>
<head>
    <script type="text/javascript">
        function begin_animate(e)  
        {  
            alert("in");
            document.getElementById("bg").style.animation-play-state="running";
            document.getElementById("pen").style.animation-play-state="running";
            document.getElementById("ch").style.animation-play-state="running";
            document.getElementById("en").style.animation-play-state="running";
        } 
    </script>
<style>

body {
    max-height: 100vh;
}

#bg {
    width: 180vw;
    height: 100vh;
    animation: curtain 2s;
    animation-fill-mode: both;
    animation-play-state: paused;
    position: absolute;
}

#pen {
    height: 50vh;
    width: 30vw;
    position: absolute;
    animation: pen 5s ease-in-out;
    animation-delay: 2.5s;
    animation-fill-mode: both;
    animation-play-state: paused;
    z-index: 5;
}

.text {
    animation: textshow both 1s 8s;
    animation-play-state: paused;
    z-index: 3;
}

#ch img {
    position: absolute;
    width: 45vw;
    right: 20vw;
    bottom: 38vh;
}

#en img {
    position: absolute;
    width: 65vw;
    right: 10vw;
    top: 63vh;
}

@keyframes curtain
{
from {right:-80vw;}
to {right:0;}
}

@keyframes pen
{
    /*from {top: -15vw; left: 38vw; opacity: 0;}*/
    /*0.01% {top: -15vw; left: 38vw; opacity: 1;}*/
    /*3.64% {top: -10vw; left: 27vw; transform: rotate(-15deg);opacity: 1;}*/
    /*10.91% {top: -5vw; left: 50vw; transform: rotate(20deg);opacity: 1;}*/
    /*12.73% {top: -1vw; left: 43vw;opacity: 1;}*/
    /*20.00% {top: -6vw; left: 60vw; transform: rotate(15deg);opacity: 1;}*/
    /*23.64% {top: -1vw; left: 70vw; transform: rotate(5deg);opacity: 1;}*/
    /*27.27% {top: -1vw; left: 73vw; transform: rotate(15deg);opacity: 1;}*/
    /*38.18% {top: 10vw; left: 45vw; transform: rotate(-20deg);opacity: 1;}*/
    /*45.45% {top: 10vw; left: 70vw; transform: rotate(10deg);opacity: 1;}*/
    /*47.27% {top: 15vw; left: 70vw; transform: rotate(-5deg);opacity: 1;}*/
    /*50.91% {top: 8vw; left: 89vw; transform: rotate(15deg);opacity: 1;}*/
    /*52.73% {top: 10vw; left: 89vw; transform: rotate(-5deg);opacity: 1;}*/
    /*58.18% {top: 13vw; left: 25vw;opacity: 1;}*/
    /*61.82% {top: 15vw; left: 28vw; transform: rotate(-10deg);opacity: 1;}*/
    /*67.27% {top: 14vw; left: 38vw;opacity: 1;}*/
    /*69.09% {top: 16vw; left: 45vw; transform: rotate(-5deg);opacity: 1;}*/
    /*70.91% {top: 14vw; left: 52vw; transform: rotate(5deg);opacity: 1;}*/
    /*72.73% {top: 16vw; left: 55vw; transform: rotate(-5deg);opacity: 1;}*/
    /*76.36% {top: 16vw; left: 66vw; transform: rotate(10deg);opacity: 1;}*/
    /*83.64% {top: 10vw; left: 70vw; transform: rotate(-10deg);opacity: 1;}*/
    /*90.91% {top: 13vw; left: 72vw; transform: rotate(-15deg);opacity: 1;}*/
    /*94.55% {top: 13vw; left: 80vw;opacity: 1;}*/
    /*96.36% {top: 13vw; left: 80vw; transform: rotate(-15deg);opacity: 1;}*/
    /*98.18% {top: 13vw; left: 85vw; transform: rotate(-5deg);opacity: 1;}*/
    /*99.99% {top: 13vw; left: 85vw; transform: rotate(-15deg);opacity: 1;}*/
    /*to  {top: 13vw; left: 85vw; transform: rotate(-15deg); opacity: 0;}*/
    from {opacity: 0; top: 15vh; left: 20vw;}
    0.01% {opacity: 100; top: 15vh; left: 20vw;}
    99.99% { top: 15vh; left: 105vw; opacity: 100;}
    to { top: 15vh; left: 105vw; opacity: 0;}
}

@keyframes textshow {
	from {opacity: 0}
	to {opacity: 100}
}

.pen {
    background-image: url(<?php bloginfo('template_directory'); ?>/images/pen.png);
    background-repeat: no-repeat;
    height: 50vh;
    width: 30vw;
    background-size: 100%;
    position: absolute;
    z-index: 5;
}

</style>
</head>
<body>
<img id="pen" src="<?php bloginfo('template_directory'); ?>/images/pen.png"/>
<img id="bg" onload="begin_animate(event)" src="<?php bloginfo('template_directory'); ?>/images/preloader.png" alt="" >
<a class="text" id="ch" href="<?php echo site_url();?>/ch/"><img src="<?php bloginfo('template_directory'); ?>/images/ch-title.png" alt=""></a>
<a class="text" href="<?php echo site_url();?>/en/" id="en"><img src="<?php bloginfo('template_directory'); ?>/images/en-title.png" alt=""></a>
</body>
</html>
