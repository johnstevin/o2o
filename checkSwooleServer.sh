count=`ps -fe |grep "swooleServer.php" | grep -v "grep" | wc -l`

echo $count
if [ $count -lt 1 ]; then
ps -eaf |grep "swooleServer.php" | grep -v "grep"| awk '{print $2}'|xargs kill -9
sleep 2
ulimit -c unlimited
php /www/o2o/swooleServer.php
echo "restart";
echo $(date +%Y-%m-%d_%H:%M:%S) >/www/o2o/Runtime/Logs/Swoole/swooleLastRestart.log
fi
