@echo off
setlocal
set "ROOT=%~dp0"
pushd "%ROOT%ionic-app"
call npm.cmd run build
if errorlevel 1 (
  echo Build failed. Fix the first reported Angular error and try again.
  popd
  pause
  exit /b 1
)
popd
echo Optimized phone app built in ionic-app\www.
pause
