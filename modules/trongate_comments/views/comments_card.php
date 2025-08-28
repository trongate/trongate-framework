<div class="card">
    <div class="card-heading">Comments</div>
    <div class="card-body">
        <div class="text-center">
            <p><button class="alt" onclick="TRONGATE_COMMENTS.openCreateCommentModal()">Add New Comment</button></p>
            <div id="comments-block"><table></table></div>
        </div>
    </div>
</div>

<div class="modal" id="upsert-comment-modal" style="display: none;">
    <div class="modal-heading"><i class="fa fa-commenting-o"></i> Add New Comment</div>
    <div class="modal-body">
        <p><textarea class="comment-input" placeholder="Enter comment here..."></textarea></p>
    </div>
    <div class="modal-footer">
        <?php
        echo form_button('close', 'Cancel', ['class' => 'alt', 'onclick' => 'closeModal()']);
        echo form_button('submit', 'Submit Comment', ['onclick' => 'TRONGATE_COMMENTS.submitComment()']);
        ?>
    </div>
</div>

<div class="modal" id="delete-comment-modal" style="display: none;">
    <div class="modal-heading danger"><i class="fa fa-trash"></i> Delete Comment</div>
    <div class="modal-body">
        <h3 class="text-center">Are you sure?</h3>
        <p>You are about to delete a comment. This cannot be undone.</p>
    </div>
    <div class="modal-footer">
        <?php
        echo form_button('close', 'Cancel', ['class' => 'alt', 'onclick' => 'closeModal()']);
        echo form_button('submit', 'Delete Comment', ['onclick' => 'TRONGATE_COMMENTS.submitDeleteComment()', 'class' => 'danger']);
        ?>
    </div>
</div>

<style>
    .editable-comment-row { display: flex; }
    .editable-comment-row button { display: flex; align-items: center; justify-content: center; }
    .editable-comment-row button i { padding: 0; margin: 0; }
    .modal-validation-error { color: red; margin-top: 0; }
</style>

<script>
    const TRONGATE_COMMENTS = {
        targetCommentCode: '',

        submitComment() {
            const commentInput = document.querySelector('#upsert-comment-modal .comment-input');
            const modalBody = commentInput.closest('.modal-body');
            const commentValue = commentInput.value;
            this.hideCommentForm(modalBody);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?= BASE_URL ?>trongate_comments/submit_comment');
            xhr.setRequestHeader('Content-type', 'application/json');
            xhr.send(JSON.stringify({
                targetTable: '<?= $target_table ?>',
                updateId: <?= (int) $update_id ?>,
                comment: commentValue,
                commentCode: this.targetCommentCode
            }));
            xhr.onload = () => {
                this.unhideCommentForm(modalBody);
                if (xhr.status === 400) {
                    const p = document.createElement('p');
                    p.className = 'modal-validation-error';
                    p.textContent = xhr.responseText;
                    modalBody.prepend(p);
                } else {
                    commentInput.value = '';
                    closeModal();
                    this.fetchComments();
                }
            };
        },

        hideCommentForm(modalBody) {
            modalBody.querySelectorAll('.modal-validation-error').forEach(p => p.remove());
            const commentInput = modalBody.querySelector('.comment-input');
            if (commentInput) commentInput.style.display = 'none';
            modalBody.closest('.modal').querySelector('.modal-footer').style.display = 'none';
            const spinner = document.createElement('div');
            spinner.className = 'spinner mt-5 mb-6';
            modalBody.appendChild(spinner);
        },

        unhideCommentForm(modalBody) {
            modalBody.querySelectorAll('.modal-validation-error').forEach(p => p.remove());
            const commentInput = modalBody.querySelector('.comment-input');
            if (commentInput) commentInput.style.display = 'block';
            modalBody.closest('.modal').querySelector('.modal-footer').style.display = 'flex';
            modalBody.querySelector('.spinner')?.remove();
        },

        fetchComments() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `<?= BASE_URL ?>trongate_comments/fetch/<?= $target_table . '/' . $update_id ?>`);
            xhr.setRequestHeader('Content-type', 'application/json');
            xhr.send();
            xhr.onload = () => {
                document.getElementById('comments-block').innerHTML = xhr.responseText;
                this.attemptAddEditables();
            };
        },

        openCreateCommentModal(commentCode = '') {
            const modalBody = document.querySelector('#upsert-comment-modal .modal-body');
            modalBody.querySelectorAll('.modal-validation-error').forEach(p => p.remove());
            openModal('upsert-comment-modal');

            const heading = document.querySelector('#upsert-comment-modal .modal-heading');
            const isEditing = commentCode !== '';
            this.targetCommentCode = isEditing ? commentCode : '';
            heading.innerHTML = `<i class="fa fa-commenting-o"></i> ${isEditing ? 'Edit' : 'Add New'} Comment`;

            if (isEditing) {
                const row = document.getElementById(commentCode);
                const cell = row.closest('td')?.querySelector('div.text-left.mt-1');
                if (cell) document.querySelector('#upsert-comment-modal .comment-input').value = cell.textContent;
            }
        },

        attemptAddEditables() {
            document.querySelectorAll('.editable-comment-row').forEach(row => {
                const fontAwesome = !!Array.from(document.getElementsByTagName('link')).find(
                    link => link.href && /font-?awesome/i.test(link.href) && link.sheet
                );
                const createButton = (icon, title, action) => {
                    const btn = document.createElement(fontAwesome ? 'button' : 'span');
                    btn[fontAwesome ? 'innerHTML' : 'textContent'] = fontAwesome ? `<i class="fa fa-${icon}"></i>` : title.toLowerCase();
                    btn.className = fontAwesome ? 'alt' : '';
                    if (!fontAwesome) btn.style.cssText = 'cursor: pointer; margin-right: 10px;';
                    btn.title = title;
                    btn.onclick = action;
                    row.appendChild(btn);
                };
                createButton('pencil', 'Edit Comment', () => this.initEditComment(row));
                createButton('trash', 'Delete Comment', () => this.initDeleteComment(row));
            });
        },

        initEditComment(row) {
            this.openCreateCommentModal(row.id);
        },

        initDeleteComment(row) {
            this.targetCommentCode = row.id;
            openModal('delete-comment-modal');
        },

        submitDeleteComment() {
            const modal = document.querySelector('#delete-comment-modal');
            const modalBody = modal.querySelector('.modal-body');
            modalBody.querySelectorAll('.modal-validation-error').forEach(p => p.remove());
            modal.querySelector('.modal-footer').style.display = 'none';
            const spinner = document.createElement('div');
            spinner.className = 'spinner mt-5 mb-6';
            modalBody.appendChild(spinner);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?= BASE_URL ?>trongate_comments/submit_delete_comment');
            xhr.setRequestHeader('Content-type', 'application/json');
            xhr.send(JSON.stringify({ commentCode: this.targetCommentCode }));
            xhr.onload = () => {
                modalBody.querySelector('.spinner').remove();
                modal.querySelector('.modal-footer').style.display = 'flex';
                if (xhr.status >= 400) {
                    const p = document.createElement('p');
                    p.className = 'modal-validation-error';
                    p.textContent = xhr.responseText;
                    modalBody.prepend(p);
                } else {
                    closeModal();
                    this.targetCommentCode = '';
                    this.fetchComments();
                }
            };
        }
    };

    TRONGATE_COMMENTS.fetchComments();
</script>