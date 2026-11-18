@echo off
setlocal
set "ROOT=%~dp0"

pushd "%ROOT%ionic-app"
echo Building DepFind web assets...
call npm.cmd run build
if errorlevel 1 goto build_failed

echo Syncing Capacitor Android assets...
call npx.cmd cap sync android
if errorlevel 1 goto build_failed

pushd android
echo Building Android debug APK...
call gradlew.bat assembleDebug
if errorlevel 1 goto android_failed
popd

copy /Y "%ROOT%ionic-app\android\app\build\outputs\apk\debug\app-debug.apk" "%ROOT%ionic-app\DepFind.apk" >nul
if errorlevel 1 goto copy_failed
popd
echo.
echo DepFind APK created at:
echo %ROOT%ionic-app\DepFind.apk
pause
exit /b 0

:android_failed
popd
echo Android build failed. Install a Java JDK and Android SDK, then try again.
popd
pause
exit /b 1

:copy_failed
popd
echo Build succeeded, but the APK could not be copied to ionic-app\DepFind.apk.
pause
exit /b 1

:build_failed
popd
echo Build or Capacitor sync failed. Fix the first reported error and try again.
pause
exit /b 1
