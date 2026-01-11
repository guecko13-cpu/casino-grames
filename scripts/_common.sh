#!/bin/bash
set -eu

source /usr/share/yunohost/helpers

app=$YNH_APP_INSTANCE_NAME

install_dir=$(ynh_app_setting_get --app="$app" --key=install_dir)
data_dir=$(ynh_app_setting_get --app="$app" --key=data_dir)
port=$(ynh_app_setting_get --app="$app" --key=port)

ensure_dirs() {
  mkdir -p "$install_dir/www" "$install_dir/www-admin" "$data_dir"
}

install_node_deps() {
  ynh_exec_as "$app" --env "PATH=$PATH_WITH_NODEJS:$PATH" --cwd "$install_dir" npm install --omit=dev
}

build_frontend() {
  if [ -d "$install_dir/frontend" ]; then
    if [ -f "$install_dir/frontend/package.json" ]; then
      ynh_exec_as "$app" --env "PATH=$PATH_WITH_NODEJS:$PATH" --cwd "$install_dir/frontend" npm install --omit=dev
      ynh_exec_as "$app" --env "PATH=$PATH_WITH_NODEJS:$PATH" --cwd "$install_dir/frontend" npm run build
    fi
    if [ -d "$install_dir/frontend/dist" ]; then
      rsync -a --delete "$install_dir/frontend/dist/" "$install_dir/www/"
    fi
  fi
}

build_admin() {
  if [ -d "$install_dir/admin" ]; then
    if [ -f "$install_dir/admin/package.json" ]; then
      ynh_exec_as "$app" --env "PATH=$PATH_WITH_NODEJS:$PATH" --cwd "$install_dir/admin" npm install --omit=dev
      ynh_exec_as "$app" --env "PATH=$PATH_WITH_NODEJS:$PATH" --cwd "$install_dir/admin" npm run build
    fi
    if [ -d "$install_dir/admin/dist" ]; then
      rsync -a --delete "$install_dir/admin/dist/" "$install_dir/www-admin/"
    fi
  fi
}
