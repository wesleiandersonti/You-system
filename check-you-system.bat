@echo off
setlocal

set "URL=http://127.0.0.1:8090/?health=1"

echo Testando %URL%
powershell -NoProfile -Command "try { $r=Invoke-WebRequest -UseBasicParsing '%URL%'; Write-Host ('HTTP '+$r.StatusCode); Write-Host $r.Content } catch { Write-Host $_.Exception.Message; exit 1 }"

endlocal
pause
