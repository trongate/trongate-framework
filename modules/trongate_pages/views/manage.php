<h1><?= $headline ?></h1>
<?php
flashdata();
echo validation_errors();
echo '<p>';
echo form_button('create', 'Create New Webpage', array('onclick' => 'openModal(\'create-page-modal\')'));
if (strtolower(ENV) === 'dev') {
    echo anchor('api/explorer/trongate_pages', 'API Explorer', array("class" => "button alt"));
}
echo '</p>';

echo Pagination::display($pagination_data);

if (count($rows) > 0) {
    $attr['class'] = 'button alt';
?>
    <table id="results-tbl">
        <thead>
            <tr>
                <th colspan="6">
                    <div>
                        <div><?php
                                echo '<form>';
                                echo form_input('searchphrase', '', array("id" => "searchphrase", "placeholder" => "Search records...", "autocomplete" => "off"));
                                echo form_button('submit', 'Search', array("class" => "alt", "onclick" => "initSearch()"));
                                echo '</form>';
                                ?></div>
                        <div>Records Per Page: <?php
                                                $dropdown_attr['onchange'] = 'setPerPage()';
                                                echo form_dropdown('per_page', $per_page_options, $selected_per_page, $dropdown_attr);
                                                ?></div>
                    </div>
                </th>
            </tr>
            <tr>
                <th class="text-center">ID</th>
                <th class="text-center">Published</th>
                <th>Page Title</th>
                <th>Author</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $attr['class'] = 'button alt';
            foreach ($rows as $row) {
                $published_icon_str = ($row->published === 'yes') ? 'fa-check-square' : 'fa-times-circle';
                $published_icon = '<i class="fa ' . $published_icon_str . '" id="published-icon-' . $row->id . '"></i>';
            ?>
                <tr>
                    <td class="text-center"><?= $row->id ?></td>
                    <td class="text-center"><?= $published_icon ?></td>
                    <td class="double-decker">
                        <div><span class="fake-link" onclick="openGoToWebpagePage('<?= $row->id ?>', '<?= $row->webpage_url ?>', '<?= $row->webpage_url_public ?>')"><?= $row->page_title ?></span></div>
                        <div class="xs"><b>URL:</b> <?= $row->webpage_url_public ?></div>
                    </td>
                    <td><?= $row->author ?></td>
                    <td class="double-decker">
                        <div><span class="smaller">Created on </span> <?= date('l jS F Y', $row->date_created) ?></div>
                        <div class="xs">Last updated: <?php 
                        if ($row->last_updated === 0) {
                            echo 'Never';
                        } else {
                            echo date('l jS F Y \a\t H:i', $row->last_updated);
                        }
                        ?></div>
                    </td>
                    <td><button class="alt" onclick="openGoToWebpagePage('<?= $row->id ?>', '<?= $row->webpage_url ?>', '<?= $row->webpage_url_public ?>')">Edit</button></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>

    <table id="results-tbl-mini">
        <thead>
            <tr>
                <th>
                    <div>
                        <div><?php
                                echo form_open('trongate_pages/manage/1/', array("method" => "get"));
                                echo form_input('searchphrase', '', array("placeholder" => "Search records...", "autocomplete" => "off"));
                                echo form_submit('submit', 'Search', array("class" => "alt"));
                                echo form_close();
                                ?></div>
                        <div>Records Per Page: <?php
                                                $dropdown_attr['onchange'] = 'setPerPage()';
                                                echo form_dropdown('per_page', $per_page_options, $selected_per_page, $dropdown_attr);
                                                ?></div>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php
            $attr['class'] = 'button alt';
            foreach ($rows as $row) {
                $status = $row->published === 'yes' ? 'Published' : 'Not published';
                $webpage_url = $row->published === 'yes' ? $row->webpage_url : 'trongate_pages/not_published/' . $row->url_string;
                $published_icon = $row->published === 'yes' ? '<i class="fa fa-check-square" id="published-icon-alt-' . $row->id . '"></i>' : '<i class="fa fa-times-circle" id="published-icon-alt-' . $row->id . '"></i>';
                $published_status = $row->published === 'yes' ? 'Published' : 'Not published';
            ?>
                <tr>
                    <td>
                        <div><span class="fake-link" onclick="openGoToWebpagePage('<?= $row->id ?>', '<?= $row->webpage_url ?>', '<?= $row->webpage_url_public ?>')"><?= $row->page_title ?></span>
                            <div class="published-status"><?= $published_icon . ' <span class="published-txt">' . $published_status ?></span></div>
                        </div>
                        <div><button class="alt" onclick="openGoToWebpagePage('<?= $row->id ?>', '<?= $row->webpage_url ?>', '<?= $row->webpage_url_public ?>')">Edit</button></div>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
<?php
    if (count($rows) > 9) {
        unset($pagination_data['include_showing_statement']);
        echo Pagination::display($pagination_data);
    }
}
?>
<div class="modal" id="create-page-modal" style="display:none">
    <div class="modal-heading"><i class="fa fa-plus"></i> Create New Page</div>
    <div class="modal-body">
        <?php
        echo form_open('trongate_pages/submit');
        echo form_label('Page Title');
        echo form_input('page_title', '', array('placeholder' => 'Enter a page title here...', 'autocomplete' => 'off'));
        echo '<p class="text-right">';
        echo form_button('close', 'Cancel', array('class' => 'alt', 'onclick' => 'closeModal()'));
        echo form_submit('submit', 'Submit');
        echo '</p>';
        echo form_close();
        ?>
    </div>
</div>

<div class="modal" id="goto-webpage-modal" style="display:none">
    <div class="modal-heading"><i class="fa fa-eye"></i> View Page</div>
    <div class="modal-body">
        <p><b>Choose from one of the following options:</b></p>
        <ul style="width: max-content; margin: 0 auto" class="mt-1">
            <li class="mt-1"><?= anchor('#', 'View Page In \'Edit\' Mode') ?></li>
            <li class="mt-1"><?= anchor('#', 'View Page As Normal Visitor') ?></li>
        </ul>
        <div id="not-published-warning">
            <i class="fa fa-warning"></i> <b>WARNING</b><br><br>
            The target webpage is currently unpublished and will not be visible to regular website visitors!
        </div>
        <p class="text-right">
            <button class="alt" onclick="closeModal()">Cancel</button>
        </p>
    </div>
</div>

<style>
    #results-tbl>thead>tr:nth-child(2)>th:nth-child(1),
    #results-tbl tbody td:nth-child(1),
    #results-tbl>thead>tr:nth-child(2)>th:nth-child(2),
    #results-tbl tbody td:nth-child(2) {
        width: 1%;
    }

    #results-tbl .fa-check-square,
    .fa-times-circle {
        font-size: 1.6em;
    }

    #results-tbl .xs {
        margin-top: 5px;
    }

    .fa-check-square {
        color: #42a8a3;
        cursor: pointer;
    }

    .fa-times-circle {
        color: #a84247;
        cursor: pointer;
    }

    .tgp-scrollable,
    .tgp-scrollable table {
        max-width: 100vw;
        overflow: auto;
    }

    #results-tbl-mini>thead>tr:nth-child(1)>th {
        padding: 0.4em;
    }

    #results-tbl-mini>thead>tr:nth-child(1)>th>div {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
    }

    #results-tbl-mini form {
        display: flex;
        flex-direction: row;
        align-items: stretch;
    }

    #results-tbl-mini>thead>tr:nth-child(1)>th>div>div:nth-child(1)>form>button.alt {
        margin: 0 4px;
        background-color: #eee !important;
        border: 1px solid var(--primary-darker);
        text-transform: none;
    }

    #results-tbl-mini>thead>tr:last-child>th:last-child {
        width: 20px;
    }

    #results-tbl-mini>tbody>tr>td:nth-child(2)>a {
        margin: 4px;
    }

    #results-tbl-mini>thead input[type="text"] {
        border: 1px solid var(--primary-darker);
    }

    #results-tbl-mini>thead>tr:nth-child(1)>th>div>div:nth-child(2)>select {
        border: 1px solid var(--primary-darker);
        width: 5em;
    }

    #results-tbl-mini .fa {
        margin-top: .6em;
        font-size: 1.2em;
        cursor: pointer;
    }

    .published-status {
        margin-top: .2em;
        font-size: .8em;
    }

    #results-tbl-mini>tbody>tr>td>div:nth-child(1)>span {
        top: -2px;
        position: relative;
        font-size: .8em;
    }

    #results-tbl-mini td {
        text-align: left;
        padding: 1em;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        font-size: 1em;
    }

    .fake-link {
        color: var(--primary);
        cursor: pointer;
    }

    .fake-link:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }

    @media (min-width: 1px) {
        #results-tbl {
            display: none;
        }

        .double-decker .xs {
            display: none;
        }

        #goto-webpage-modal>div.modal-body>p:nth-child(1) {
            display: none;
        }
    }

    #not-published-warning {
        background-color: #a8424733;
        border: 1px #a8424744 solid;
        padding: 1em 2em;
        margin-top: 2em;
        font-size: .9em;
        border-radius: 6px;
        line-height: 1.3em;
    }

    @media (min-width: 720px) {
        #goto-webpage-modal>div.modal-body>p:nth-child(1) {
            display: block;
        }
    }

    @media (min-width: 900px) {
        #results-tbl-mini {
            display: none;
        }

        #results-tbl {
            display: table;
        }
    }

    @media (min-width: 1500px) {
        .double-decker .xs {
            display: block;
        }
    }
</style>


<script>
    const arrayOne = document.querySelectorAll('#results-tbl > tbody > tr > td.text-center > i.fa');
    const arrayTwo = document.querySelectorAll('#results-tbl-mini > tbody > tr > td > div:nth-child(1) > div > i.fa');
    const publishIcons = Array.from(arrayOne).concat(Array.from(arrayTwo));
    for (var i = 0; i < publishIcons.length; i++) {
        publishIcons[i].addEventListener('click', (ev) => {
            togglePublishedStatus(ev.target);
        });
    }

    function togglePublishedStatus(clickedIcon) {
        const newPublishedStatus = (clickedIcon.classList.contains('fa-times-circle')) ? 1 : 0;
        const elId = clickedIcon.id;
        let recordId = elId.replace('published-icon-alt-', '');
        recordId = recordId.replace('published-icon-', '');
        recordId = parseInt(recordId);

        const params = {
            published: newPublishedStatus
        }

        clickedIcon.style.display = 'none';
        const targetUrl = '<?= BASE_URL ?>api/update/trongate_pages/' + recordId;
        const http = new XMLHttpRequest();

        http.open('put', targetUrl);
        http.setRequestHeader('Content-type', 'application/json');
        http.setRequestHeader('trongateToken', '<?= $token ?>');
        http.send(JSON.stringify(params));
        http.onload = (ev) => {
            if (http.status == 200) {
                togglePublishedIcon(clickedIcon);
            }
        };

    }

    function togglePublishedIcon(clickedIcon) {
        //update the icon that was clicked
        const {
            classList,
            id
        } = clickedIcon;
        const isTimesCircle = classList.contains('fa-times-circle');
        const newClass = isTimesCircle ? 'fa-check-square' : 'fa-times-circle';
        const oldClass = isTimesCircle ? 'fa-times-circle' : 'fa-check-square';
        const publishedTitle = (newClass === 'fa-check-square') ? 'Published' : 'Not published';
        clickedIcon.classList.remove(oldClass);
        clickedIcon.classList.add(newClass);
        clickedIcon.style.display = 'inline-block';
        const currentTable = id.includes('published-icon-alt') ? 'results-tbl-mini' : 'results-tbl';
        const recordId = id.replace('published-icon-alt-', '').replace('published-icon-', '');
        const altIconId = currentTable === 'results-tbl-mini' ? `published-icon-${recordId}` : `published-icon-alt-${recordId}`;
        const altIcon = document.getElementById(altIconId);
        altIcon.classList.remove(oldClass);
        altIcon.classList.add(newClass);

        //find the icon that is within the 'mini' table
        const miniIcon = document.querySelector('#published-icon-alt-' + recordId);
        const publishedStatusDiv = miniIcon.closest('.published-status');
        const publishedTxtEl = publishedStatusDiv.querySelector('.published-txt');
        publishedTxtEl.innerHTML = publishedTitle;
    }

    function openGoToWebpagePage(recordId, webpageUrl, webpageUrlPublic) {
        const targetIcon = document.getElementById('published-icon-' + recordId);
        const isPublished = (targetIcon.classList.contains('fa-times-circle')) ? 'no' : 'yes';

        openModal('goto-webpage-modal');
        setTimeout(() => {
            const firstLink = document.querySelector('#goto-webpage-modal > div.modal-body > ul > li:nth-child(1) > a');
            firstLink.href = webpageUrl + '/edit';
            const secondLink = document.querySelector('#goto-webpage-modal > div.modal-body > ul > li:nth-child(2) > a');
            secondLink.href = webpageUrlPublic;
            const notPublishedWarning = document.getElementById('not-published-warning');
            notPublishedWarning.style.display = isPublished === 'yes' ? 'none' : 'block';
        }, 1);
    }

    function initSearch() {
        const searchInput = document.getElementById('searchphrase');
        const encodedString = encodeURIComponent(searchInput.value);
        const targetUrl = '<?= BASE_URL ?>trongate_pages/manage/1/?searchphrase=' + encodedString;
        window.location.href = targetUrl;
    }
</script>