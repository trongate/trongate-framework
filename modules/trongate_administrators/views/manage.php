<h1>Manage Administrators</h1>
<?= flashdata('<p style="color:lime;">', '</p>') ?>
<p>
<?php
$attr['class'] = 'button';  
echo anchor('trongate_administrators/create', 'Create New Record <i class="fa fa-pencil"></i></button>', $attr); 
?>
</p>

<table>
	<thead>
		<tr>
			<th>ID</th>
			<th>Username</th>
			<th style="width: 100px;">Action</th>
		</tr>
		<?php 
		foreach($rows as $row) { ?>
		<tr>
			<td><?= $row->id ?></td>
			<td><?= $row->username ?></td>
			<td style="text-align: center;">
				<?php  
				$edit_url = BASE_URL.'trongate_administrators/create/'.$row->id;
				echo anchor($edit_url, 'Edit <i class="fa fa-pencil"></i></button>', $attr);
				?>
			</td>
		</tr>
		<?php  
		}
		?>
	</thead>
	<tbody id="records">
	</tbody>
</table>