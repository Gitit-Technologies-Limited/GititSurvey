; Bootstrap for this file
(php
  (list
    ('req "application/controllers/AdminController.php")
    ('req "application/core/LSYii_Controller.php")
    ('req "application/helpers/remotecontrol/remotecontrol_handle.php")
    ('req "tests/bootstrap.php")
    ('req "vendor/autoload.php")))

; PHP Unit test
(test-class 'remotecontrol_handle
  (constructor (new 'AdminController "dummy"))

  (test-method
    'add_participants
    (setq (participant (list (map ("firstname" "John")))))
    (setq (surveyId 1))
    (setq (sessionKey "abc123"))
    (arguments sessionKey surveyId participant 'false)
    (result (list (map ("firstname" "John")))))

  (test-method
    'foo
    (arguments 1 2 3)
    (result 10))
)
