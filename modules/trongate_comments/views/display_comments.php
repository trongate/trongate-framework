<table>
	<tbody>
		<?php
		foreach($comments as $comment) {
        ?>
        <tr>
            <td style="padding: 1em;">
            	<div class="flex-row align-center justify-between">
            		<div>Comment posted by <b><?= out($comment->username) ?></b> on <?= date('l jS F Y \a\t H:i', $comment->date_created) ?></div>
            		<div<?php
            		if ($comment->user_id == $trongate_user_id) {
            			echo ' class="editable-comment-row sm" id="'.$comment->code.'"';
            		}
            	    ?>></div>
            	</div>
            	<div class="text-left mt-1"><?= nl2br(out($comment->comment)) ?></div>
            </td>
        </tr>
        <?php
		}
		?>
	</tbody>
</table>