<?php

    /*
     * @author Joshua Kissoon
     * @date 20120101
     * @description User class for the system
     */

    class User extends JUser
    {

       public function fullName()
       {
          /*
           * Returns the user's full name
           */
          return $this->first_name . " " . $this->last_name;
       }

    }