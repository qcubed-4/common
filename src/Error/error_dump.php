<?php
use QCubed\Project\Application;
use QCubed\Project\Control\ControlBase;
use QCubed\Project\Control\FormBase as Form;

// Define default values for variables if they are not already set
$__exc_strMessage = $__exc_strMessage ?? 'Unknown Error';
$__exc_strFilename = $__exc_strFilename ?? '';
$__exc_strType = $__exc_strType ?? 'Error';
$__exc_strObjectType = $__exc_strObjectType ?? 'Unknown Type';
$__exc_intLineNumber = $__exc_intLineNumber ?? 1;
$__exc_strStackTrace = $__exc_strStackTrace ?? 'No stack trace available.';

/**
 * error_page include a file
 *
 * expects the following variables to be set:
 * 	$__exc_strType
 * 	$__exc_strMessage
 * 	$__exc_strObjectType
 * 	$__exc_strFilename
 * 	$__exc_intLineNumber
 * 	$__exc_strStackTrace
 *
 * optional:
 * 	$__exc_strRenderedPage
 *  $__exc_objErrorAttributeArray
 *
 * Tries to accomplish as much as possible without calling into the QCubed framework, since we are in an error state.
 */

$__exc_strMessageBody = htmlentities($__exc_strMessage, ENT_QUOTES, 'UTF-8', false);
$__exc_strMessageBody = str_replace(" ", "&nbsp;", str_replace("\n", "<br/>\n", $__exc_strMessageBody));
$__exc_strMessageBody = str_replace(":&nbsp;", ": ", $__exc_strMessageBody);

if (file_exists($__exc_strFilename)) {
	$__exc_objFileArray = file($__exc_strFilename);
} else {
	$__exc_objFileArray = array();
}

header("HTTP/1.1 500 Internal Server Error");
if (class_exists('\QCubed\Project\Application')) {
    Application::setProcessOutput(false);
}
?>
<!DOCTYPE html>
<?php
if (stristr($__exc_strMessage, "Invalid Form State Data") !== false) {
    // This was invalid form state data
    // We return this string because the invalid form state data error response doesn't behave like other errors,
    // and we can't render a dialog box for the error. Since qcubed.js looks for '<html>' at the beginning of
    // the response to display it in a new window, the following line avoids this behavior
	echo '<!-- -->';
}
?>
<html lang="en" class="no-js">
	<head>
		<title>PHP <?= htmlentities($__exc_strType); ?> - <?= htmlentities($__exc_strMessage) ?></title>
        <link href="<?= QCUBED_CSS_URL ?>/qcubed.css" rel="stylesheet">
	</head>
	<body>
		<header>
			<span><?= htmlentities($__exc_strType); ?> in PHP Script</span> <?= htmlentities($_SERVER["PHP_SELF"]); ?>
		</header>
		<section id="content">
			<h1><?= $__exc_strMessageBody ?></h1>
			<p><strong><?= htmlentities($__exc_strType); ?> Type:</strong> <?= htmlentities($__exc_strObjectType); ?></p>
<?php
			if (isset($__exc_strRenderedPage)) {
				$_SESSION['RenderedPageForError'] = $__exc_strRenderedPage;
?>
				<p><strong>Rendered Page:</strong>
					<a target="_blank" href="<?= htmlentities(QCUBED_PHP_URL); ?>/error_already_rendered_page.php">Click here to view contents able to be rendered.</a>
				</p>
<?php
			}
?>
			<p><strong>Source File:</strong> <?= htmlentities($__exc_strFilename); ?> <strong>Line:</strong> <?= htmlentities($__exc_intLineNumber); ?></p>

			<pre><code><?php
			for ($__exc_intLine = max(1, $__exc_intLineNumber - 5); $__exc_intLine <= min(count($__exc_objFileArray), $__exc_intLineNumber + 5); $__exc_intLine++) {
				if ($__exc_intLineNumber == $__exc_intLine){
					printf("<span class='warning'>Line %s:    %s</span>", $__exc_intLine, htmlentities($__exc_objFileArray[$__exc_intLine - 1]));
				}else{
					printf("Line %s:    %s", $__exc_intLine, htmlentities($__exc_objFileArray[$__exc_intLine - 1]));
				}
			}
?></code></pre>
<?php
			if (defined('ERROR_EMAIL')) {
				$style = '';
			} else {
				$style = 'display: none;';
			}

			if (isset($__exc_objErrorAttributeArray)) {
				foreach ($__exc_objErrorAttributeArray as $__exc_objErrorAttribute) {
					printf("<p><strong>%s:</strong>&nbsp;&nbsp;", $__exc_objErrorAttribute->Label);
					$__exc_strJavascriptLabel = str_replace(" ", "", $__exc_objErrorAttribute->Label);
					if ($__exc_objErrorAttribute->MultiLine) {
						printf("\n<a href=\"#\" onclick=\"ToggleHidden('%s'); return false;\">Show/Hide</a></p>", $__exc_strJavascriptLabel);
						printf('<pre><code id="%s" %s >%s</code></pre>', $__exc_strJavascriptLabel, $style, htmlentities($__exc_objErrorAttribute->Contents));
					} else {
						printf("%s</p>\n", htmlentities($__exc_objErrorAttribute->Contents));
					}
				}
			}
?>

			<p><strong>Call Stack:</strong></p>
			<pre><code><?= htmlentities($__exc_strStackTrace); ?></code></pre>

			<p><strong>Variable Dump:</strong> <a href="#" onclick="ToggleHidden('VariableDump'); return false;">Show/Hide</a></p>
			<pre><code id="VariableDump" <?php if (!defined('ERROR_EMAIL')) { ?> style="display: none;" <?php } ?> ><?php
				// Dump All Variables
				foreach ($GLOBALS as $__exc_Key => $__exc_Value) {
					// TODO: Figure out why this is so strange
					if (isset($__exc_Key))
						if ($__exc_Key != "_SESSION")
							global $$__exc_Key;
				}

				$__exc_ObjVariableArray = get_defined_vars();
				$__exc_ObjVariableArrayKeys = array_keys($__exc_ObjVariableArray);
				sort($__exc_ObjVariableArrayKeys);

				$__exc_StrToDisplay = "";
				$__exc_StrToScript = "";
				$varCounter = 0;
				foreach ($__exc_ObjVariableArrayKeys as $__exc_Key) {
					if ((!str_contains($__exc_Key, "__exc_")) && (!str_contains($__exc_Key, "_DATE_")) && ($__exc_Key != "GLOBALS") && !($__exc_ObjVariableArray[$__exc_Key] instanceof Form)) {
						try {
							if (($__exc_Key == 'HTTP_SESSION_VARS') || ($__exc_Key == '_SESSION')) {
                                $__exc_ObjSessionVarArray = array_filter($$__exc_Key, function ($__exc_StrSessionKey) {
                                    return !str_starts_with($__exc_StrSessionKey, 'qform');
                                }, ARRAY_FILTER_USE_KEY);
								$__exc_StrVarExport = htmlentities(var_export($__exc_ObjSessionVarArray, true));
							} else if (($__exc_ObjVariableArray[$__exc_Key] instanceof ControlBase)) {
								$__exc_StrVarExport = htmlentities($__exc_ObjVariableArray[$__exc_Key]->varExport());
							} else {
								$__exc_StrVarExport = htmlentities(var_export($__exc_ObjVariableArray[$__exc_Key], true));
							}

							$__exc_StrToDisplay .= sprintf("<a style='display:block' href='#%s' onclick='javascript:ToggleHidden(\"%s\"); return false;'>%s</a>", $varCounter, $varCounter, $__exc_Key);

							$__exc_StrToDisplay .= sprintf("<span id=\"%s\" %s >%s</span>", $varCounter, $style, $__exc_StrVarExport);
							$varCounter++;
						} catch (Exception $__exc_objExcOnVarDump) {
							//$__exc_StrToDisplay .= sprintf("Fatal error:  Nesting level too deep - recursive dependency?\n", $__exc_objExcOnVarDump->getMessage());
						}
					}
				}

				echo($__exc_StrToDisplay);
?>
                </code></pre>
		</section>

		<hr />
		
		<p style="text-align: center;"><em><?= htmlentities($__exc_strType); ?> Report Generated:&nbsp;<?= htmlentities(date('l, F j Y, g:i:s A')); ?></em></p>
		
		<footer>
			<strong>PHP Version:</strong> <?= htmlentities(PHP_VERSION); ?>;&nbsp;<strong>Zend Engine Version:</strong> <?= htmlentities(zend_version()); ?>;&nbsp;<strong>QCubed Version:</strong> <?= htmlentities(QCUBED_VERSION); ?><br />
			<?php if (array_key_exists('OS', $_SERVER)) printf('<strong>Operating System:</strong> %s;&nbsp;&nbsp;', $_SERVER['OS']); ?><strong>Application:</strong> <?= htmlentities($_SERVER['SERVER_SOFTWARE']); ?>;&nbsp;<strong>Server Name:</strong> <?= htmlentities($_SERVER['SERVER_NAME']); ?><br />
			<strong>HTTP User Agent:</strong> <?= htmlentities($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'); ?>
		</footer>
	<?php printf('<script type="text/javascript">%s</script>', $__exc_StrToScript); ?>
	<script type="text/javascript">
		function ToggleHidden(strDiv) {
            const obj = document.getElementById(strDiv);
            const stlSection = obj["style"];
            const isCollapsed = obj["style"].display.length;
            if (isCollapsed) { stlSection.display = ''; }else{ stlSection.display = 'none'; }
		}
	</script>
</body>
</html>
