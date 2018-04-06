<?php

function groundhog_day_drush_command() {
  return [
    'groundhog-day-update' => [
      'aliases' => ['ghdu'],
    ],
    'groundhog-day-reset' => [
      'aliases' => ['ghdr'],
    ],
  ];
}

function drush_groundhog_day_update() {
  \Drupal::service('groundhog_day.executor')->update();
}

function drush_groundhog_day_reset() {
  \Drupal::service('groundhog_day.executor')->reset();
}
