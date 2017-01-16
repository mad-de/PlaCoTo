<?php
if(!isset($_GET["step"]))
{
$students = fetch_students();
$output_create_new_user = <<< EOT
<form action="admin.php?act=edit_users&step=submit" method="POST" autocomplete="off">
EOT;
$count = 0;
$groups = fetch_groups();
foreach($students as $student)
{
$group_options = "";
foreach($groups as $current_group)
{
	if($current_group->ID == $student->GROUP)
	{ 
		$group_options .= '<option value="' . $current_group->ID . '" selected >' . $current_group->NAME . '</option>';
	}
	else
	{ 
		$group_options .= '<option value="' . $current_group->ID . '">' . $current_group->NAME . '</option>';
	}
}
	if($student->STATUS == "ADMIN")
	{ 
		$status_options = '<option value="ADMIN" selected >ADMIN</option><option value="USER">USER</option><option value="UNCONFIRMED">UNCONFIRMED</option>';
	}
	elseif($student->STATUS == "USER")
	{ 
		$status_options = '<option value="ADMIN">ADMIN</option><option value="USER" selected >USER</option><option value="UNCONFIRMED">UNCONFIRMED</option>';
	}
	elseif($student->STATUS == "UNCONFIRMED")
	{ 
		$status_options = '<option value="ADMIN">ADMIN</option><option value="USER">USER</option><option value="UNCONFIRMED" selected >UNCONFIRMED</option>';
	}
$output_create_new_user .= <<< EOT
        <input id="name" name="{$count}::id" type="hidden" value="{$student->ID}">
      <label for="name">Name:
        <input id="name" name="{$count}::name" value="{$student->NAME}">
      </label>
      <label for="login">Login:
        <input id="name" name="{$count}::login" value="{$student->LOGIN}">
      </label>
	  <label for="passwort">Passwort:
        <input id="name" name="{$count}::password" type="password">
      </label>
      <label for="email">E-mail:
        <input id="name" name="{$count}::email" value="{$student->EMAIL}">
      </label>
<label for="group">Group:
    <select name="{$count}::group">
$group_options
    </select>
      <label for="karma">Karma:
        <input id="name" name="{$count}::karma" value="{$student->KARMA}">
      </label>
	  <label for="karma">Joker:
        <input id="name" name="{$count}::joker" value="{$student->JOKER}">
      </label>
<label for="status">Status:
    <select name="{$count}::status">
$status_options
    </select>
  </label>
<a href="admin.php?act=edit_users&step=delete_user&id={$student->ID}" />Delete User</a>
<br />
EOT;
$count++;
}
$count--;
$output_create_new_user .= <<< EOT
        <input id="name" name="total_num" type="hidden" value="{$count}">
	   <button type="submit">Submit</button>
</form>
EOT;
}
elseif($_GET["step"] == "submit")
{
$old_students = fetch_students();

for ($input_count = 0; $input_count <= $_POST["total_num"]; $input_count++) 
	{
	$new_student_table[$input_count] = new db_students;
    	$new_student_table[$input_count]-> ID = $_POST[$input_count . "::id"];
    	$new_student_table[$input_count]-> NAME = $_POST[$input_count . "::name"];
	$new_student_table[$input_count] -> LOGIN = $_POST[$input_count . "::login"];
	if(empty($_POST[$input_count . "::password"]))
	{
		foreach($old_students as $old_student)
		{
			if($old_student->ID == $_POST[$input_count . "::id"])	
			{ $new_student_table[$input_count] -> PASSWORD = $old_student->PASSWORD; }  
		}
	}
	else
	{
		$new_student_table[$input_count] -> PASSWORD = md5($_POST[$input_count . "::password"]);
	}
	$new_student_table[$input_count] -> STATUS = $_POST[$input_count . "::status"];
	$new_student_table[$input_count] -> GROUP = $_POST[$input_count . "::group"];
	$new_student_table[$input_count] -> EMAIL = $_POST[$input_count . "::email"];
	$new_student_table[$input_count] -> KARMA = $_POST[$input_count . "::karma"];
	$new_student_table[$input_count] -> JOKER = $_POST[$input_count . "::joker"];
	}
	if(file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'students.json', json_encode($new_student_table)))
	{
		$output_create_new_user = 'Student table succesfully altered. <a href="admin.php?act=edit_users" />Back to overview</a>.';
	}
	else
	{
		$output_create_new_user = 'Error updating the database.';
	}
}
elseif($_GET["step"] == "delete_user")
{
	$student_table = fetch_json_table('students.json');
	$student_count = 0;
	foreach($student_table as $this_table_student) 
	{
		if($this_table_student["ID"] != $_GET["id"])
		{
			$students[$student_count] = new db_students;
			$students[$student_count] -> ID = $this_table_student["ID"];
			$students[$student_count] -> NAME = $this_table_student["NAME"];
			$students[$student_count] -> LOGIN = $this_table_student["LOGIN"];
			$students[$student_count] -> PASSWORD = $this_table_student["PASSWORD"];
			$students[$student_count] -> STATUS = $this_table_student["STATUS"];
			$students[$student_count] -> GROUP = $this_table_student["GROUP"];
			$students[$student_count] -> EMAIL = $this_table_student["EMAIL"];
			$students[$student_count] -> KARMA = $this_table_student["KARMA"];
			$students[$student_count] -> JOKER = $this_table_student["JOKER"];
			$student_count = $student_count + 1;
		}
	}
	if(file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'students.json', json_encode($students)))
	{
		$output_create_new_user = "User with ID " . $_GET["id"] . " succesfully deleted.";
	}
}
$module_output = $output_create_new_user;
?>
