@echo on

set base=%~dp0
set cwd=%CD%

set pdir=%base%..\
set js_dir=%pdir%htdocs\scripts

pushd %pdir%\coffee

for %%f in (*.coffee) do (
	coffee -b -c -o "%js_dir%" %%f
)

popd

@echo on
