<section>
    <h1 class="text-center blink">It Totally Works!</h1>
    <h2 class="mt-1">Welcome to Trongate - the revolutionary new framework for developers who love pure PHP.</h2>
    <p class="text-center lg">
        <?php
        echo anchor('tg-admin', 'Admin Panel', array('class' => 'button'));
        echo anchor('https://trongate.io/learning-zone', 'Learning Zone', array('class' => 'button alt'));
        ?>
    </p>
</section>

<section>
    <h3>Getting Started</h3>
    <p>You can log into the admin panel by going to <a href="<?= BASE_URL ?>tg-admin">tg-admin</a>. The default login credentials are as follows:</p>
    <ul>
        <li><b>Username:</b> admin</li>
        <li><b>Password:</b> admin</li>
    </ul>
    <p class="sm"><b>PLEASE NOTE:</b>
        The <?= anchor('https://trongate.io/learning-zone', 'Learning Zone', array('target' => '_blank')) ?> is a great starting place if you're new to Trongate. If you need help, head over to the <?= anchor('https://trongate.io/help_bar', 'Help Bar', array('target' => '_blank')) ?>.</p>
</section>

<hr>

<section>
    <h2>Why Trongate?</h2>

    <p class="mb-3">Trongate takes inspiration from languages like GoLang, C, and C++. It is built with a focus on lightning-fast performance, while minimizing dependencies on third-party libraries. By adopting this approach, Trongate delivers not only exceptional speed but also rock-solid stability.  <b>If you like pure PHP, you'll love Trongate!</b></p>

    <table class="framework-benchmarks">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <th colspan="4">Requests / second</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>Framework</th>
                <th>Test #1</th>
                <th>Test #2</th>
                <th>Test #3</th>
                <th>Avg</th>
            </tr>
            <tr>
                <td>Trongate</td>
                <td>1056.96</td>
                <td>1016.44</td>
                <td>1044.64</td>
                <td>1039</td>
            </tr>
            <tr>
                <td>Lumen</td>
                <td>203.3</td>
                <td>202.97</td>
                <td>203.96</td>
                <td>203</td>
            </tr>
            <tr>
                <td>CodeIgniter 3</td>
                <td>110.27</td>
                <td>107.84</td>
                <td>199.79</td>
                <td>139</td>
            </tr>
            <tr>
                <td>Yii2</td>
                <td>130.32</td>
                <td>128.82</td>
                <td>133.43</td>
                <td>131</td>
            </tr>
            <tr>
                <td>CodeIgniter 4</td>
                <td>109.08</td>
                <td>128.91</td>
                <td>129.08</td>
                <td>122</td>
            </tr>
            <tr>
                <td>Symfony 4</td>
                <td>53.74</td>
                <td>57.8</td>
                <td>57.05</td>
                <td>56</td>
            </tr>
            <tr>
                <td>Laravel 8</td>
                <td>48.8</td>
                <td>54.76</td>
                <td>53.32</td>
                <td>52</td>
            </tr>
        </tbody>
    </table>

    <p class="sm mt-3"><b>SMALL PRINT</b> The above test results above were recorded, independently, by Adam Spencer.  Since carrying out the tests, several of the alternative PHP frameworks above have been rewritten.  We look forward to repeating the tests, in the near future, using more recent versions of alternative frameworks.  We expect more of the same.  Those tests, when they happen, will be recorded and upload to YouTube.</p>
</section>

<hr>

<section>
    <h3 class="text-center">Six ways that you can help Trongate</h3>
    <p class="mb-2">Trongate surpasses other PHP frameworks in every crucial metric, yet you never hear it being mentioned by the likes of PHP News.   
        Regrettably, the self-proclaimed PHP aficionados tend to discourage innovative, alternative approaches. That's precisely why we need your help! 
        Here are six ways you can contribute to Trongate and make a difference.
    </p>
    <div class="quote-area mt-3">
        <div class="quotation">&ldquo;When you build a framework, like Trongate, that's completely different from everything else, you offend a lot of people.&rdquo;</div>
        <div class="text-right mt-1"><b>Chad Tate - the most searched for PHP developer on the internet</b></div>
    </div>
    <div class="fact-checks mt-3">
        <div class="card">
            <div class="card-heading">
                <i class="fa fa-github"></i> GitHub Stars
            </div>
            <div class="card-body">
                <p class="sm"><b>Trongate is the 12th most popular active PHP framework, based on GitHub stars.  Help us to reach the top ten and be a part of history!</b></p>
                <p class="text-center"><?= anchor('https://github.com/trongate/trongate-framework', '<i class="fa fa-github"></i> Go To GitHub', array('class' => 'button')) ?></p>
            </div>
        </div>
        <div class="card">
            <div class="card-heading">
                <i class="fa fa-twitter"></i> Tweet About Trongate
            </div>
            <div class="card-body">
                <p class="sm"><b>Most PHP developers haven't heard of Trongate.  Help us to change that!  Post a tweet about Trongate and help us to spread the word!</b></p>
                <p class="text-center"><?= anchor('https://twitter.com/intent/tweet?text=I%27m%20checking%20out%20the%20Trongate%20PHP%20framework!%20Check%20it%20out%20at%20&url=https%3A%2F%2Ftrongate.io', '<i class="fa fa-twitter"></i> Compose Tweet', array('class' => 'button')) ?></p>
            </div>
        </div>
        <div class="card">
            <div class="card-heading">
                <i class="fa fa-youtube-play"></i> Post A Tutorial
            </div>
            <div class="card-body">
                <p class="sm"><b>Please consider posting a tutorial, if you can.  Web development tutorials (particularly on YouTube) are essential and we need more of them!</b></p>
                <p class="text-center"><button onclick="openModal('my-modal-1')">Learn More</button></p>
            </div>
        </div>
        <div class="card">
            <div class="card-heading">
                <i class="fa fa-commenting-o"></i> Talk About Trongate
            </div>
            <div class="card-body">
                <p class="sm"><b>Join the conversation! Write articles, share insights, and speak about Trongate on podcasts and elsewhere. We need your voice!</b></p>
                <p class="text-center"><button onclick="openModal('my-modal-2')">Learn More</button></p>
            </div>
        </div>
        <div class="card">
            <div class="card-heading">
                <i class="fa fa-code"></i> Write Code
            </div>
            <div class="card-body">
                <p class="sm"><b>Trongate is an open source PHP framework and we welcome contributions from our growing army of talented PHP developers.</b></p>
                <p class="text-center"><button onclick="openModal('my-modal-3')">Learn More</button></p>
            </div>
        </div>
        <div class="card">
            <div class="card-heading">
                <i class="fa fa-shield"></i> Defend Trongate
            </div>
            <div class="card-body">
                <p class="sm"><b>Have you spotted somebody making a false technical declaration about Trongate? Tell us! We're on a mission to fact check all of the naysayers.</b></p>
                <p class="text-center"><button onclick="openModal('my-modal-4')">Learn More</button></p>
            </div>
        </div>
    </div>
</section>

<p class="sm mt-3">Trongate utilizes the open-source MIT license, which is widely recognized and respected within the software licensing community. This license grants users the freedom to employ Trongate for commercial purposes, providing reassurance that there will be no unfavorable consequences.</p>

<!-- modals code -->
<div id="my-modal-1" class="modal" style="display: none">
    <div class="modal-heading"><i class="fa fa-youtube-play"></i> Post A Tutorial</div>
    <div class="modal-body">
            <p class="sm">Web development tutorials, especially on platforms like YouTube, play a crucial role in educating and empowering developers. By sharing your knowledge and expertise, you can contribute to the growth of the community. Consider creating tutorials on topics that you are passionate about and that you believe would benefit others in their web development journey.</p>
        <p class="sm">We encourage you to explore various tutorial formats, such as step-by-step coding examples, walkthroughs of complex concepts, and practical project tutorials. Remember, your tutorials can make a significant impact and help fill the gap in the availability of quality web development resources. Happy tutorial creation!</p>    
        <p class="text-center"><button class="alt" onclick="closeModal('my-modal-1')">Close</button></p>
    </div>
</div>
<div id="my-modal-2" class="modal" style="display: none">
    <div class="modal-heading"><i class="fa fa-commenting-o"></i> Talk About Trongate</div>
    <div class="modal-body">
           <p class="sm">Writing articles, blog posts, or even contributing to discussions on forums and social media platforms can help spread awareness about Trongate and its benefits.</p>
        <p class="sm">Consider creating content that showcases your experiences, tutorials, case studies, or even comparisons with other frameworks. Your insights can help developers make informed decisions and highlight the strengths of Trongate.</p>
        <p class="sm">Furthermore, speaking about Trongate on podcasts, webinars, or conferences can reach a wider audience and foster meaningful discussions. Sharing your expertise and experiences can inspire others and contribute to the growth of the Trongate community.</p>
        <p class="text-center"><button class="alt" onclick="closeModal('my-modal-2')">Close</button></p>
    </div>
</div>
<div id="my-modal-3" class="modal" style="display: none">
    <div class="modal-heading"><i class="fa fa-code"></i> Write Code</div>
    <div class="modal-body">
        <p class="sm">Trongate is open source.  We greatly value contributions from our community. By contributing to our codebase on GitHub, you become a part of our growing army of developers committed to building a faster and better way to do PHP.</p>
        <p class="sm">If you're interested in contributing, head over to our <?= anchor('https://github.com/trongate/trongate-framework', 'GitHub repository') ?> and explore the open issues, feature requests, or areas for improvement. You can submit pull requests, offer code enhancements, or help address bugs. Additionally, consider sharing your ideas, insights, and knowledge through documentation improvements or community support.</p>
        <p class="sm">Together, we can shape the future of Trongate and create a powerful PHP framework that benefits developers worldwide.</p>
        <p class="text-center"><button class="alt" onclick="closeModal('my-modal-3')">Close</button></p>
    </div>
</div>
<div id="my-modal-4" class="modal" style="display: none">
    <div class="modal-heading"><i class="fa fa-shield"></i> Defend Trongate</div>
    <div class="modal-body">
        <p class="sm">Trongate may not appeal to everyone, and that's alright. Criticism is welcome and can even be helpful. It's not necessary for everyone to like Trongate! However, spreading false technical information is both unethical and - sometimes - malicious.</p>
        <p class="sm">If you encounter any false technical claims or misunderstandings about Trongate, we encourage you to report them to us.</p>
        <p class="sm">Your feedback is essential in our mission to provide reliable information to the wider web development community regarding Trongate. Help us to <?= anchor('https://www.youtube.com/watch?v=FfirQfFgh9A', 'fact-check') ?> the critics! Our objective is to ensure that developers have access to accurate details about the framework's capabilities, features, and benefits.</p>
        <p class="sm">If you spot anyone dishing out false information about Trongate, please feel free to reach out to us via our <?= anchor('https://trongate.io/contactus', 'contact form') ?>. We'll take care of the rest.</p>
        <p class="text-center"><button class="alt" onclick="closeModal('my-modal-4')">Close</button></p>
    </div>
</div>

<style>
.framework-benchmarks {
    max-width: 640px;
    margin: 0 auto;
    font-size: .833em;
}

body > div.wrapper > main > section > table > thead > tr > th {
    background-color: #37678f;
    border:  1px #000 solid;
    text-transform: uppercase;
}

body > div.wrapper > main > section > table > thead > tr > th:nth-child(1) {
    background-color: white;
    border: 1px white solid;
}

body > div.wrapper > main > section > table > tbody > tr > td:nth-child(1),
body > div.wrapper > main > section > table > tbody > tr > td:nth-child(5) {
    font-weight: bold;
}

body > div.wrapper > main > section > table > tbody > tr:nth-child(2) {
    background-color: #a6fc8c;
}

body > div.wrapper > main > section > table > tbody > tr > td:nth-child(2),
body > div.wrapper > main > section > table > tbody > tr > td:nth-child(3),
body > div.wrapper > main > section > table > tbody > tr > td:nth-child(4),
body > div.wrapper > main > section > table > tbody > tr > td:nth-child(5) {
    text-align: center;
}

.fact-checks {
    display: grid;
    grid-gap: 1em;
}

div.modal-body p {
    text-align: left;
}

@media screen and (min-width: 1px) {
    .fact-checks {
        grid-template-columns: 1fr;
    }
}

@media screen and (min-width: 760px) {
    .fact-checks {
        grid-template-columns: repeat(3, 1fr);
    }
}

.quote-area {
    background-color: #fdfdea;
    border: 1px #99bbd8 solid;
    font-family: Garamond, serif;
    padding: 1em;
    max-width: 640px;
    margin: 0 auto;
    border-radius: 12px;
}

.quote-area .quotation {
    font-weight: bold;
    font-size: 1.33em;
}
</style>