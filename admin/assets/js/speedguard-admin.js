/**
 * SpeedGuard JavaScript for Admin
 */
document.addEventListener(
    'DOMContentLoaded',
    function () {
        // Adding SG headers to the default WP list table
        //test-type-psi
        const table = document.querySelector("table.toplevel_page_speedguard_tests");
        const thead = table.getElementsByTagName("thead")[0];
        const testTypeRow = document.createElement("tr");
        if (table.classList.contains('psi-test-type')) {
           // console.log('it does psi!');
            testTypeRow.innerHTML = '< th colspan      = "2" > < / th > < th colspan      = "2" class = "test-type-thead psi-mobile"> PSI < / th > < th colspan      = "2" class = "test-type-thead psi-desktop" > PSI < / th >';
            var column_count = 2;
        }

        if (table.classList.contains('cwv-test-type')) {
           // console.log('it does cwv!');
            testTypeRow.innerHTML = '< th colspan      = "2" > < / th > < th colspan      = "3" class ="test-type-thead cwv-mobile" > CWV < / th > < th colspan      = "3" class = "test-type-thead cwv-desktop" > CWV < / th > ';
            var column_count = 3;
        }

        // For Debugging Only    thead.prepend(testTypeRow);
        const deviceRow = document.createElement("tr");
        deviceRow.innerHTML = "<th colspan='2'></th><th colspan='" + column_count + "'><i class='sg-device-column mobile' aria-hidden='true' title='Mobile'></i></th><th colspan='" + column_count + "'><i class='sg-device-column desktop' aria-hidden='true' title='Desktop'></i></th>";
        thead.prepend(deviceRow);

        // Mark scores with colors
        const testscore = document.querySelectorAll(".speedguard-score");
        for (const testScore of testscore) {
            //const dataScore = testScore.getAttribute("data-score");
            const dataCategory = testScore.getAttribute("data-score-category");
            if (dataCategory > 0.7 || dataCategory === "FAST") {
                testScore.classList.add("score-green");
            } else if (dataCategory > 0.4 || dataCategory === "AVERAGE") {
                testScore.classList.add("score-yellow");
            } else {
                testScore.classList.add("score-red");
            }
        }

        const metaboxes = document.querySelectorAll(".postbox");
        for (const metabox of metaboxes) {
            const toggleButton = metabox.querySelector(".handlediv");
            toggleButton.addEventListener(
                "click",
                () => {
                    metabox.classList.toggle("closed");
                }
            );
        }
    }
);
