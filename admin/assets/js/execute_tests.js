/**
 * SpeedGuard JavaScript for Running Tests
 */

async function fetchAll(url_to_test) {

    const request_url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?category=performance&url=' + url_to_test + '&';
    const devices = ['mobile', 'desktop'];

    try {
        singleURLresult = [];
        let tests = await Promise.all(
            devices.map(device => fetch(request_url + 'strategy=' + device)
                .then(r => r.json())
                .catch(error => ({ error, url}))
            )
        )
        for (let item of tests) {
            //get current device value
            const device = item.lighthouseResult.configSettings.emulatedFormFactor;
            //Data fro Single URL (both CWV and PSI)
            const URL_RESULTS = { "cwv" : {
                    "lcp": item.loadingExperience.metrics.LARGEST_CONTENTFUL_PAINT_MS, // percentile, distributions, category
                    "cls": item.loadingExperience.metrics.CUMULATIVE_LAYOUT_SHIFT_SCORE,
                    "fid": item.loadingExperience.metrics.FIRST_INPUT_DELAY_MS,
                    // "overall_category": item.loadingExperience.overall_category
                },
                "psi" : {
                    "lcp": item.lighthouseResult.audits['largest-contentful-paint'].numericValue,
                    "cls": item.lighthouseResult.audits['cumulative-layout-shift'].numericValue,
                    "fid": item.lighthouseResult.audits['max-potential-fid'].numericValue,
                    //  "overall_category": item.lighthouseResult.categories.performance.score
                }
            };
            //Data for CWV Origin
            const Origin_CWV = {
                "lcp": item.originLoadingExperience.metrics.LARGEST_CONTENTFUL_PAINT_MS,
                "cls": item.originLoadingExperience.metrics.CUMULATIVE_LAYOUT_SHIFT_SCORE,
                "fid": item.originLoadingExperience.metrics.FIRST_INPUT_DELAY_MS,
                "overall_category": item.originLoadingExperience.overall_category
            };

            //Save data to the nenw object based on device value
            let singleURLresultperdevice = {
                [device]: {"psi": URL_RESULTS.psi, "cwv": URL_RESULTS.cwv}
            };
            singleURLresult.push(singleURLresultperdevice);

            console.log(singleURLresult);
        }

        console.log(singleURLresult);
    } catch (err) {
        console.log(err)
    }
}
