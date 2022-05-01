<?php

/**
 * Simple chat example by Stephan Soller
 * See http://arkanis.de/projects/simple-chat/
 */

// Name of the message buffer file. You have to create it manually with read and write permissions for the webserver.
$messages_buffer_file = 'messages_new.json';
// Number of most recent messages kept in the buffer
$messages_buffer_size = 10;

if (isset($_POST['name']) )
{
	// Open, lock and read the message buffer file
	$buffer = fopen($messages_buffer_file, 'r+b');
	flock($buffer, LOCK_EX);
	$buffer_data = stream_get_contents($buffer);

	// Append new message to the buffer data or start with a message id of 0 if the buffer is empty
	$messages = $buffer_data ? json_decode($buffer_data, true) : array();
	$next_id = (count($messages) > 0) ? $messages[count($messages) - 1]['id'] + 1 : 0;
	$messages[] = array('id' => $next_id, 'time' => time(), 'name' => $_POST['name'], 'color' => $_POST['clr'], 'height' => $_POST['height'], 'width' => $_POST['width']);

	// Remove old messages if necessary to keep the buffer size
	if (count($messages) > $messages_buffer_size)
		$messages = array_slice($messages, count($messages) - $messages_buffer_size);

	// Rewrite and unlock the message file
	ftruncate($buffer, 0);
	rewind($buffer);
	fwrite($buffer, json_encode($messages));
	flock($buffer, LOCK_UN);
	fclose($buffer);

	// Optional: Append message to log file (file appends are atomic)
	//file_put_contents('chatlog.txt', strftime('%F %T') . "\t" . strtr($_POST['name'], "\t", ' ') . "\t" . strtr($_POST['content'], "\t", ' ') . "\n", FILE_APPEND);

	exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>field pal</title>
	<link href="css/style.css" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" type="text/javascript"></script>
  <script   src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"
  integrity="sha256-hlKLmzaRlE8SCJC1Kw8zoUbU8BxA+8kR3gseuKfMjxA="
  crossorigin="anonymous"></script>


</head>
<body>

<div id="container">

	<div class="title">
		field pal
	</div>

  <div id="farbe">
	<form action="<?= htmlentities($_SERVER['PHP_SELF'], ENT_COMPAT, 'UTF-8'); ?>" method="post">

			<div>
		    <input class="resizable xy" type="color" id="clr" name="clr" value="#BCC873">
			</div>
			<div class="abstand">
				<label>
				 <input class="label" type="text" name="name" id="name" value="field" required>
				</label>
			</div>
			<div class="abstand">
				<button type="submit text">
					seed
				</button>
			</div>

	</form>
	</div>

<div id="felder">
	<ul id="messages">
		<li>loading…</li>
	</ul>
</div>

</div>

<script type="text/javascript">
		$( ".resizable" ).resizable();
	// <![CDATA[
	$(document).ready(function(){
		// Remove the "loading…" list entry
		$('ul#messages > li').remove();

		$('form').submit(function(){
			var form = $(this);

			var name =  form.find("input[name='name']").val();

			var clr =  form.find("input[name='clr']").val();
			var height =  form.find("input[name='clr']").height();
			var width =  form.find("input[name='clr']").width();

			// Only send a new message if it's not empty (also it's ok for the server we don't need to send senseless messages)
			if (name == '')
				return false;

			// Append a "pending" message (not yet confirmed from the server) as soon as the POST request is finished. The
			// text() method automatically escapes HTML so no one can harm the client.
			$.post(form.attr('action'), {
				'name': name,
				'clr': clr,
				'height': height,
				'width': width,
			}, function(data, status){
				$('<li class="pending" style="height:' +height+ 'px; width:' +width+ 'px; background-color:'+clr+'" />').prepend($('<small />').text(name)).appendTo('ul#messages');

				$('ul#messages').scrollTop( $('ul#messages').get(0).scrollHeight );

				form.find("input[name='name']").val('').focus();
			});
			return false;
		});

		// Poll-function that looks for new messages
		var poll_for_new_messages = function(){
			console.log("poll")

			$.ajax({url: 'messages_new.json', dataType: 'json', ifModified: true, timeout: 2000, success: function(messages, status){

				// Skip all responses with unmodified data
				if (!messages)
					return;

				$('ul#messages > li').remove();

				for(var i = 0; i < messages.length; i++)
				{
					var msg = messages[i];

                    $('<li style="height:' +msg.height+ 'px; width:' +msg.width+ 'px; background-color:'+msg.color+'" />').prepend($('<small />').text(msg.name)).appendTo('ul#messages');
				}

				// Remove all but the last 50 messages in the list to prevent browser slowdown with extremely large lists
				// and finally scroll down to the newes message.
				$('ul#messages > li').slice(0, -50).remove();
				$('ul#messages').scrollTop( $('ul#messages').get(0).scrollHeight );
			}});
		};

		// Kick of the poll function and repeat it every two seconds
		poll_for_new_messages();
		setInterval(poll_for_new_messages, 2000);
	});
	// ]]>
</script>

</body>
</html>
