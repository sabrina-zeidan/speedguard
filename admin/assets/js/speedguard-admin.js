document.addEventListener('DOMContentLoaded', function () {
    //Mark tests according to scores
    var testscore = document.getElementsByClassName("speedguard-score");
    var i;
    for (i = 0; i < testscore.length; i++) {
        var datascore = testscore[i].getAttribute('data-score');
        if ( datascore > 0.7 ) {
            testscore[i].classList.add("score-green");
        } else if ( datascore > 0.4 ) {
            testscore[i].classList.add("score-yellow");
        } else {
            testscore[i].classList.add("score-red");
        }
    }
    
    //WP Dashboard Metaboxes open/close
    var a = document.getElementsByClassName("postbox");
    var i;
    for (let i = 0; i < a.length; i++) {
        var togglebutton = a[i].querySelector('.handlediv');
        togglebutton.addEventListener('click', function () {
            a[i].classList.toggle("closed");
        });
    }
});