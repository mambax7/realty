<?php
include("admin_header.php");
xoops_cp_header();

	include("../include/common.php");
	loginCheck('User');
	global $action, $id, $cur_page, $lang, $conn, $config, $delete, $edit;
	include("$config[template_path]/admin_top.html");

	if ($delete != "")
	{
		$sql_delete = make_db_safe($delete);
		// delete a auto
		$sql = "DELETE FROM ".$config['table_prefix']."autodb WHERE ((ID = $sql_delete) AND (user_ID = $userID))";
		$recordSet = $conn->Execute($sql);
		if ($recordSet === false) log_error($sql);

		// delete all the elements associated with a auto
		$sql = "DELETE FROM ".$config['table_prefix']."autodbelements WHERE ((auto_id = $sql_delete) AND (user_id = $userID))";
		$recordSet = $conn->Execute($sql);
		if ($recordSet === false)
		{
			log_error($sql);
		}

		// now get all the images associated with an auto
		$sql = "SELECT file_name, thumb_file_name FROM ".$config['table_prefix']."autoimages WHERE ((auto_id = $sql_delete) AND (user_id = $userID))";
		$recordSet = $conn->Execute($sql);
		if ($recordSet === false)
		{
			log_error($sql);
		}

		// so, you've got 'em... it's time to unlink those bad boys...
		while (!$recordSet->EOF)
		{
			$thumb_file_name = make_db_unsafe ($recordSet->fields[thumb_file_name]);
			$file_name = make_db_unsafe ($recordSet->fields[file_name]);
			// get rid of those darned things...
			if (!unlink("$config[auto_upload_path]/$file_name"))
			{
				die("$lang[alert_site_admin]");
			}
			if ($file_name != $thumb_file_name)
			{
				if (!unlink("$config[auto_upload_path]/$thumb_file_name"))
				{
					die("$lang[alert_site_admin]");
				}
			}
			$recordSet->MoveNext();
		}

		// for the grand finale, we're going to remove the db records of 'em as well...
		$sql = "DELETE FROM ".$config['table_prefix']."autoimages WHERE ((auto_id = $sql_delete) AND (user_id = $userID))";
		$recordSet = $conn->Execute($sql);
		if ($recordSet === false) 
		{
			log_error($sql);
		}

		// ta da! we're done...
		echo "<p>$lang[admin_listings_editor_listing_number] '$delete' $lang[has_been_deleted]</p>";
		log_action ("$lang[log_deleted_listing] $delete");
	}

	if ($action == "update_auto")
	{
		// update the auto
		if ($title== "")
		{
			// if the title is blank
			echo "$lang[admin_new_listing_enter_a_title]<br>";
		} // end if

		else
		{

			global $HTTP_POST_VARS, $userID, $pass_the_form;
			$pass_the_form = validateForm('autoformelements');
			if ($pass_the_form == "No")
			{
				// if we're not going to pass it, tell that they forgot to fill in one of the fields
				echo "<p>$lang[required_fields_not_filled]</p>";
			}

			if ($pass_the_form == "Yes")
			{
				$sql_title = make_db_safe($title);
				$sql_notes = make_db_safe($notes);
				$sql_edit = make_db_safe($edit);
				

				$sql = "UPDATE ".$config['table_prefix']."autodb SET title = $sql_title, ";
				if ($featureListings == "yes")
				{
					// if the user can feature properties
					$sql_featured = make_db_safe($featured);
					$sql .= "featured = $sql_featured, ";
				} // end if ($featureListings == "yes")
				if ($admin_privs == "yes")
				{
					// if the user can feature properties
					$sql_active = make_db_safe($edit_active);
					$sql .= "active = $sql_active, ";
				} // end if ($admin_privs == "yes")
				if (($admin_privs == "yes" OR $config['user_default_canChangeExpirations'] == "yes")and $config['use_expiration'] = "yes")
				{
					//$date_array = explode("-",$edit_expiration);
					//$exp_text = implode(",",$date_array);
					//$expiration_date  = mktime (0,0,0,$exp_text);
					$expiration_date = strtotime($edit_expiration);
					$sql .= "expiration = ".$conn->DBDate($expiration_date).",";
				}


				$sql .= "notes = $sql_notes, last_modified = ".$conn->DBTimeStamp(time())." WHERE ((ID = $sql_edit) AND (user_ID = $userID))";
				$recordSet = $conn->Execute($sql);
				if ($recordSet === false) 


				{
					log_error($sql);
				}
				$message = updateautoData($edit, $userID);
				if ($message == "success")
				{
					echo "<p>$lang[admin_autos_editor_auto_number] $edit $lang[has_been_updated] </p>";
					log_action ("$lang[log_updated_auto] $edit");
				} // end if
				else
				{
					echo "<p>$lang[alert_site_admin]</p>";
				} // end else
			} // end if $pass_the_form == "Yes"
		} // end else
	} // end if $action == "update auto"


	if ($edit != "")
	{
		$sql_edit = make_db_safe($edit);
		// first, grab the autos's main info
		$sql = "SELECT ID, title, notes, last_modified, featured, active, expiration FROM ".$config['table_prefix']."autodb WHERE ((ID = $sql_edit) AND (user_ID = '$userID'))";
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$recordSet = $conn->Execute($sql);
		if ($recordSet === false)
		{
			log_error($sql);
		}

		$num_records = $recordSet->RecordCount();
		if ($num_records == 0)
		{
			die("$lang[priv_failure]");
		}

		while (!$recordSet->EOF)
		{
			// collect up the main DB's various fields
			$auto_ID = make_db_unsafe ($recordSet->fields['ID']);
			$edit_title = make_db_unsafe ($recordSet->fields['title']);
			$edit_notes = make_db_unsafe ($recordSet->fields['notes']);
			$edit_featured = make_db_unsafe ($recordSet->fields['featured']);
			$edit_active = make_db_unsafe ($recordSet->fields['active']);
			$last_modified = $recordSet->UserTimeStamp($recordSet->fields['last_modified'],'D M j G:i:s T Y');
			$expiration = $recordSet->UserTimeStamp($recordSet->fields['expiration'],'Y-m-d');
			$formatted_expiration = $recordSet->UserTimeStamp($recordSet->fields['expiration'],'D M j Y');
			
			$recordSet->MoveNext();
		} // end while
		//$date_mod = getDate($last_modified);



		//$last_modified = "$date_mod[weekday], $date_mod[month] $date_mod[mday] $date_mod[year] at $date_mod[hours]:$date_mod[minutes]" // display the date in a human readable format
		// now, display all that stuff
		?>

		<table border="<?php echo $style['form_border'] ?>" cellspacing="<?php echo $style['form_cellspacing'] ?>" cellpadding="<?php echo $style['form_cellpadding'] ?>" width="<?php echo $style['admin_table_width'] ?>" class="form_main">
			<tr>
				<td colspan="2" class="row_main">
					<h3><?php echo "$lang[admin_autos_editor_modify_auto]"?> (<a href="<?php echo "$config[baseurl]"."/autoview.php?autoID=$auto_ID";?>" target="_preview"><?php echo $lang['preview'] ?></a>)</h3>
				</td>
			</tr>
			<tr>
				<td width="<?php echo $style[image_column_width] ?>" valign="top" align="center" class="row_main">
					<b><?php echo $lang['images'] ?></b>
					<br>
					<hr width="75%">
					<a href="edit_autovtour_images.php?edit=<?php echo $edit ?>"><?php echo $lang[edit_autovtour] ?></a>
					<hr width="75%">
					<a href="edit_auto_images.php?edit=<?php echo $edit ?>"><?php echo $lang['edit_images'] ?></a><br><br>
					<?php
					$sql = "SELECT caption, file_name, thumb_file_name FROM ".$config['table_prefix']."autoimages WHERE ((auto_id = '$edit') AND (user_id = '$userID'))";
					$recordSet = $conn->Execute($sql);
						if ($recordSet === false) log_error($sql);

					$num_images = $recordSet->RecordCount();

					while (!$recordSet->EOF)
						{
						$caption = make_db_unsafe ($recordSet->fields['caption']);
						$thumb_file_name = make_db_unsafe ($recordSet->fields['thumb_file_name']);
						$file_name = make_db_unsafe ($recordSet->fields['file_name']);

						// gotta grab the image size
						$imagedata = GetImageSize("$config[auto_upload_path]/$thumb_file_name");
						$imagewidth = $imagedata[0];
						$imageheight = $imagedata[1];
						$shrinkage = $config['thumbnail_width']/$imagewidth;
						$displaywidth = $imagewidth * $shrinkage;
						$displayheight = $imageheight * $shrinkage;

						echo "<a href=\"$config[auto_view_images_path]/$file_name\" target=\"_thumb\"> ";

						echo "<img src=\"$config[auto_view_images_path]/$thumb_file_name\" height=\"$displayheight\" width=\"$displaywidth\"></a><br> ";
						echo "<b>$caption</b><br><br>";
						$recordSet->MoveNext();
						} // end while
					?>
				</td>
				<td class="row_main">

				<table border="<?php echo $style['form_border'] ?>" cellspacing="<?php echo $style['form_cellspacing'] ?>" cellpadding="<?php echo $style['form_cellpadding'] ?>">
					<form name="update_auto" action="<?php echo "$PHP_SELF";?>" method="post">
					<input type="hidden" name="action" value="update_auto">
					<input type="hidden" name="edit" value="<?php echo $edit ?>">


					<tr>
						<td align="right" class="row_main"><b><?php echo $lang['admin_listings_editor_title'] ?>: <span class="required">*</span></b></td>
						<td align="left"> <input type="text" name="title" value="<?php echo $edit_title ?>"></td>
					</tr>
					<?php
						if ($featureListings == "yes")
						{
						?>
						<tr><td align="right"><b><?php echo $lang['admin_listings_editor_featured']  ?>:</b></td><td align="left">
						<select name="featured" size="1">
							<option value="<?php echo $edit_featured ?>"><?php echo $edit_featured ?>
							<option value="">-----
							<option value="yes">yes
							<option value="no">no
						</select>
						<?php
						} // end if ($featureListings == "yes")
						if ($admin_privs == "yes")
						{
							?>
							<tr><td align="right"><b><?php echo $lang['admin_listings_active'] ?>:</b></td><td align="left">
							<select name="edit_active" size="1">
							<option value="<?php echo $edit_active ?>"><?php echo $edit_active ?>
							<option value="">-----
							<option value="yes">yes
							<option value="no">no
							</select>
							<?php
						}
						if (($admin_privs == "yes" OR $config['user_default_canChangeExpirations'] == "yes")and $config['use_expiration'] = "yes")
						{
						?>
						
						<tr><td align="right" class="row_main"><b><?php echo $lang['expiration'] ?>:</b></td><td align="left"><input type="text" name="edit_expiration" value="<?php echo $expiration ?>">(Y-M-D)</td></tr>

						<?php
						} // end if ($admin_privs == "yes" and $config[use_expiration] = "yes")
						elseif ($config['use_expiration'] = "yes")
						{
						?>
						<tr><td align="right" class="row_main"><b><?php echo $lang['expiration'] ?>:</b></td><td align="left"> <?php echo $formatted_expiration ?></td></tr>

						<?php
						} // end if  ($config[use_expiration] = "yes")
						?>
					<tr><td align="right" class="row_main"><b><?php echo $lang['last_modifed'] ?>:</b></td><td align="left"> <?php echo $last_modified ?></td></tr>
					<tr><td align="right" class="row_main"><b><?php echo $lang['admin_listings_editor_notes'] ?>:</b><br><div class="small">(<?php echo $lang['admin_listings_editor_notes_note'] ?>)</div></td><td align="left"> <textarea name="notes" rows="6" cols="40"><?php echo $edit_notes ?></textarea></td></tr>

		<?php
		echo '<script src="../date.js"></script>';
		$sql = "SELECT f.field_name, db.field_value, f.field_type, f.field_caption, f.default_text, f.field_elements, f.required FROM ".$config['table_prefix']."autoformelements f left join ".$config['table_prefix']."autodbelements db on db.field_name = f.field_name and db.auto_id = '$edit' AND db.user_id = '$userID' ORDER BY rank";

		$recordSet = $conn->Execute($sql);
		if ($recordSet === false) 
		{
			log_error($sql);
		}
		while (!$recordSet->EOF)
		{
			$field_name = make_db_unsafe ($recordSet->fields['field_name']);
			$field_value = make_db_unsafe ($recordSet->fields['field_value']);
			$field_type = make_db_unsafe ($recordSet->fields['field_type']);
			$field_caption = make_db_unsafe($recordSet->fields['field_caption']);
			$default_text = make_db_unsafe($recordSet->fields['default_text']);
			$field_elements = make_db_unsafe($recordSet->fields['field_elements']);
			$required = make_db_unsafe($recordSet->fields['required']);
			// pass the data to the function
			renderExistingFormElement($field_type, $field_name, $field_value, $field_caption, $default_text, $required, $field_elements);

			$recordSet->MoveNext();
		}
		echo "<tr><td colspan=\"2\" align=\"center\" class=\"row_main\">$lang[required_form_text]</td></tr>";

		echo "<tr><td colspan=\"2\" align=\"center\" class=\"row_main\"><input type=\"submit\" value=\"$lang[update_button]\"></td></tr></table></form>";

		?>
		</td></tr></table>
		<?php
	} // end if $edit != ""
	if ($edit == "")
	{
		echo "<h3>$lang[autos_editor]</h3>";
		// show all a user's given autos

		// grab the number of autos from the db
		$sql = "SELECT ID, title, notes, expiration, active FROM ".$config['table_prefix']."autodb WHERE user_ID = '$userID'";
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$recordSet = $conn->Execute($sql);
		if ($recordSet === false) log_error($sql);
		$num_rows = $recordSet->RecordCount();

		next_prev($num_rows, $cur_page, ""); // put in the next/previous stuff

		// build the string to select a certain number of autos per page
		$limit_str = $cur_page * $config['listings_per_page'];

		$recordSet = $conn->SelectLimit($sql, $config['listings_per_page'], $limit_str );
		if ($recordSet === false) 
		{
			log_error($sql);
		}
		$count = 0;
		echo "<br><br>";
		while (!$recordSet->EOF)
		{

			// alternate the colors
			if ($count == 0)
			{
				$count = $count +1;
			}
			else
			{
				$count = 0;
			}

			//strip slashes so input appears correctly
			$title = make_db_unsafe ($recordSet->fields['title']);
			$notes = make_db_unsafe ($recordSet->fields['notes']);
			$active = make_db_unsafe ($recordSet->fields['active']);
			$formatted_expiration = $recordSet->UserTimeStamp($recordSet->fields['expiration'],'D M j Y');
			$ID = $recordSet->fields['ID'];

			?>
			<table border="<?php echo $style['admin_auto_border'] ?>" cellspacing="<?php echo $style['admin_auto_cellspacing'] ?>" cellpadding="<?php echo $style['admin_auto_cellpadding'] ?>" width="<?php echo $style['admin_table_width'] ?>" class="form_main">
			<?php
			echo "<tr><td align=\"right\" width=\"200\" class=\"row1_$count\"><B><span class=\"adminautoLeft_$count\">$lang[admin_autos_editor_auto_number]: $ID</b></span></td><td align=\"center\" class=\"row2_$count\" width=\"310\"> <B> <a href=\"$PHP_SELF?edit=$ID\">$lang[admin_autos_editor_modify_auto]</a></b></td><td width=120 align=middle class=\"row2_$count\"><a href=\"$PHP_SELF?delete=$ID\" onClick=\"return confirmDelete()\">$lang[admin_autos_editor_delete_auto]</a></td></tr>";
			echo "<tr><td align=\"center\" valign=\"middle\" class=\"row3_$count\">$title";
			echo "</td><td class=\"row3_$count\">$notes";
			if ($config['use_expiration'] == "yes")
			{
				echo "<br><br><b>$lang[expiration]</b>: $formatted_expiration";
			}
			if ($active != "yes")
			{
				echo "<br><font color=\"red\">$lang[this_listing_is_not_active]</font></b>";
			}
			echo "</td>";
			echo "<td class=\"row3_$count\">&nbsp;</td></tr></table><br><br>\r\n\r\n";
			$recordSet->MoveNext();
		} // end while
	} // end if $edit == ""
?>


	<P>
	</P>

<?php
	include("$config[template_path]/admin_bottom.html");
	$conn->Close(); // close the db connection

xoops_cp_footer();
include("../../../footer.php");
?>