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

<div class="comments">
    <p id="comments-info"></p>
    <div id="comments-so-far"></div>
</div>

<script>
var token = '<?= $token ?>';
var target_table = '<?= $target_table ?>';
var update_id = '<?= $update_id ?>';

function fetch_comments() {

    var target_url = '<?= BASE_URL ?>api/get/comments/?target*!underscore!*table=' + target_table + '&update*!underscore!*id=' + update_id + '&orderBy=date*!underscore!*created';

    const http = new XMLHttpRequest()
    http.open('GET', target_url)
    http.setRequestHeader('Content-type', 'application/json')
    http.setRequestHeader("trongateToken", token)
    http.send()
    http.onload = function() {
        // Do whatever with response
        var comments = JSON.parse(http.responseText);

        var commentsTbl = '<table class="w3-table w3-striped" id="comments-tbl">';

        for (var i = comments.length - 1; i >= 0; i--) {

            commentsTbl = commentsTbl.concat('<tr><td><p class="w3-small">' + comments[i]['date_created'] + 
                '</p><p>' + comments[i]['comment'] + '</p></td></tr>');
        }

        commentsTbl = commentsTbl.concat('</table>');

        if (comments.length>0) {
            var commentInfo = '';
        } else {
            var commentInfo = 'No comments have been posted so far.';
        }

        document.getElementById("comments-info").innerHTML = commentInfo;
        document.getElementById("comments-so-far").innerHTML = commentsTbl;
    }

}

function submitNewComment() {
    var comment = document.getElementById("new-comment").value;
    comment = comment.trim();

    if (comment == "") {
        return;
    } else {

        document.getElementById("create-comment-modal").style.display='none';
        document.getElementById("new-comment").value = '';

        const params = {
            comment,
            target_table,
            update_id
        }

        var target_url = '<?= BASE_URL ?>api/create/comments';
        const http = new XMLHttpRequest()
        http.open('POST', target_url)
        http.setRequestHeader('Content-type', 'application/json')
        http.setRequestHeader("trongateToken", token)
        http.send(JSON.stringify(params)) 
        http.onload = function() {
            fetch_comments();
        }

    }

}



















fetch_comments();
</script>