<?php
$pid = (int) $argv[1];
echo "Generating signal SIGQUIT {$pid}...\n";
posix_kill($pid, SIGQUIT);
echo "Done\n";
