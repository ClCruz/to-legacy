    <div id='content'>
    	<div id='app'>
			<?php
				if (isset($_GET['p'])) {
					if (file_exists($_GET['p'].'.php')) {
						include $_GET['p'].'.php';
					} else {
						echo '<h2>Fun&ccedil;&atilde;o n&atilde;o encontrada!</h2>';
					}
				}
			?>
		</div>
    </div>