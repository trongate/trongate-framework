<p><button onclick="document.getElementById('create-comment-modal').style.display='block'" class="w3-button w3-white w3-border"><i class="fa fa-commenting-o"></i> ADD NEW COMMENT</button></p>

<div id="create-comment-modal" class="w3-modal" style="padding-top: 7em;">
    <div class="w3-modal-content w3-animate-top w3-card-4" style="width: 30%;">
        <header class="w3-container primary w3-text-white">
            <h4><i class="fa fa-commenting-o"></i> ADD NEW COMMENT</h4>
        </header>
        <div class="w3-container">
            <p>
                <textarea name="comment" id="new-comment" class="w3-input w3-border w3-sand" placeholder="Enter comment here..." ></textarea>
            </p>
            <p class="w3-right modal-btns">
                <button onclick="document.getElementById('create-comment-modal').style.display='none'" type="button" name="submit" value="Submit" class="w3-button w3-small 3-white w3-border">CANCEL</button> 

                <button onclick="submitNewComment()" type="button" name="submit" value="Submit" class="w3-button w3-small primary">ADD COMMENT</button> 
            </p>
        </div>
    </div>
</div>

<script type="text/javascript">
    var modals = document.getElementsByClassName("w3-modal");

    window.onclick = function(event) {
      for (var i = 0; i < modals.length; i++) {
          if (event.target == modals[i]) {
            modals[i].style.display = "none";
          }
      }
    }
</script>

<div class="comments">
    <?php 
    if ($comments == false) {
        echo '<p id="comments-info">No comments have been posted so far.</p>';
    } else {
        echo '<p id="comments-info"></p>';
    }
    ?>

    <table class="w3-table w3-striped" id="comments-tbl">
        <?php
        if (gettype($comments) == 'array') {
            foreach ($comments as $row) {
                $date_created = date('l jS \of F Y \a\t h:i:s A', $row->date_created);
            ?>
                <tr>
                    <td>
                        <p class="w3-small"><?= $date_created ?></p>
                        <p><?= $row->comment ?></p>
                    </td>
                </tr>
            <?php
            }
        }
        ?>
    </table>

</div>

<script>
var token = '<?= $token ?>';

function submitNewComment() {
    document.getElementById('create-comment-modal').style.display='none';
    var newComment = document.getElementById('new-comment').value;

    if (newComment != '') {
        
        const params = {
            target_table: '<?= $target_table ?>',
            update_id: '<?= $update_id ?>',
            token,
            comment: newComment
        }

        document.getElementById('new-comment').value = '';

        const http = new XMLHttpRequest()
        http.open('POST', '<?= BASE_URL ?>comments/submit')
        http.setRequestHeader('Content-type', 'application/json')
        http.send(JSON.stringify(params)) // Make sure to stringify
        http.onload = function() {
            // Update the comments table
            token = http.responseText;
            refreshComments();
        }
    }
}

function refreshComments() {

    var commentsTblInnerHTML = '';

    const params = {
        target_table: '<?= $target_table ?>',
        update_id: '<?= $update_id ?>',
        token
    }

    const http = new XMLHttpRequest()
    http.open('POST', '<?= BASE_URL ?>comments/get')
    http.setRequestHeader('Content-type', 'application/json')
    http.send(JSON.stringify(params)) // Make sure to stringify

    http.onload = function() {
        // Display the comments
        var comments = JSON.parse(http.responseText);
        if (comments.length > 0) {
            for (var i = 0; i < comments.length; i++) {
                var newRow = '<tr><td><p class="w3-small">' + comments[i]['date_created'] + '</p><p>' + comments[i]['comment'] + '</p></td></tr>';
                commentsTblInnerHTML += '<tr><td><p class="w3-small">' + comments[i]['date_created'] + '</p><p>' + comments[i]['comment'] + '</p></td></tr>'; 
            }
        }

        document.getElementById('comments-tbl').innerHTML = commentsTblInnerHTML;
        document.getElementById('comments-info').style.display = 'none';
    }
}
</script>    