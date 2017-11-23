<!DOCTYPE html>
<html>
<head>
	<title>Confirmation Email</title>
</head>
<body>
	<h1>Thank you for Sign Up!</h1>
	<p>
		<!-- You need to <a href='{{ url("register/confirmation/{$user->token}") }}'> Confirm your Email Address</a> -->
		You need to <a href='{{ url("http://beta.desktopnexus.com/reset/{$user->id}") }}'> Reset your Password</a>
	</p>
</body>
</html>