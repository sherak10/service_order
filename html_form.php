<?php

require 'db_connection.php';

class html_form {
    
    private $tag;
    private $html;
    private $form;
    private $form_name;
    private $success_msg = '';

    function __construct($form_name) {
        static $json = null;
        if($json == null) {
            $json = file_get_contents('forms.json');
            $json = json_decode($json, true);
        }

        $this->form_name = $form_name;
        $this->form = [];
        foreach($json[$form_name] as $field) {
            $this->form[$field['name']] = $field;
            $this->form[$field['name']]['invalid'] = false;
        }
    }

    function add_attributes($attr_ar) {
        $str = '';
        // check minimized (boolean) attributes
        $min_atts = array('checked', 'disabled', 'readonly', 'multiple',
                'required', 'autofocus', 'novalidate', 'formnovalidate'); 
        foreach($attr_ar as $key=>$val) {
            if(!$key)
                continue;
            if(in_array($key, $min_atts)) {
                if(!empty($val)) { 
                    $str .= " $key";
                }
            } else {
                $str .= " " . htmlentities((string)$key) . "=" . htmlentities((string)$val);
            }
        }
        return $str;
    }

    function start_form($action='#', $method='post', $id='', $attr_ar=array()) {
        $str = "<form action=\"$action\" method=\"$method\"";
        if(!empty($id)) {
            $str .= " id=\"$id\"";
        }
        $str .= $attr_ar ? $this->add_attributes($attr_ar) . '>': '>';
        return $str;
    }
    
    function add_input($type, $name, $value, $attr_ar = array()) {
        $str = "<input type=\"$type\" name=\"$name\" value=\"" . htmlentities($value) . "\"";
        if($attr_ar) {
            $str .= $this->add_attributes($attr_ar);
        }
        $str .= '>';
        return $str;
    }

    function add_button($type, $name, $value) {
        $str = "<button type=\"$type\" name=\"$name\">";
        $str .= htmlentities($value) . '</button>';
        return $str;
    }
    
    function add_textarea($name, $rows=4, $cols=30, $value='', $attr_ar=array()) {
        $str = "<textarea name=\"$name\" rows=\"$rows\" cols=\"$cols\"";
        if($attr_ar) {
            $str .= $this->add_attributes( $attr_ar );
        }
        $str .= ">" . htmlentities($value) . "</textarea>";
        return $str;
    }
    
    function add_label($id, $text, $attr_ar=array()) {
        $str = "<label for=\"$id\"";
        if($attr_ar) {
            $str .= $this->add_attributes($attr_ar);
        }
        $str .= ">" . htmlentities($text) . "</label>";
        return $str;
    }
    
    // option values and text come from one array (can be assoc)
    // $check_val false if text serves as value (no value attr)
    function add_select_list($name, $option_list, $check_val=true, $selected_value=NULL,
            $header=NULL, $attr_ar=array()) {
        $str = "<select name=\"$name\"";
        if($attr_ar) {
            $str .= $this->add_attributes($attr_ar);
        }
        $str .= ">\n";
        if(isset($header)) {
            $str .= "  <option value=\"\">$header</option>\n";
        }
        foreach($option_list as $val => $text) {
            $str .= $check_val ? "  <option value=\"$val\"": "  <option";
            if(isset($selected_value) && ($selected_value === $val || $selected_value === $text)) {
                $str .= ' selected';
            }
            $str .= ">$text</option>\n";
        }
        $str .= "</select>";
        return $str;
    }

    function add_select_list_arrays($name, $val_list, $txt_list, $selected_value=NULL,
            $header=NULL, $attr_ar=array()) {
        $option_list = array_combine($val_list, $txt_list);
        $str = $this->addSelectList($name, $option_list, true, $selected_value, $header, $attr_ar);
        return $str;
    }
    
    function start_tag($tag, $attr_ar=array()) {
        $this->tag = $tag;
        $str = "<$tag";
        if($attr_ar) {
            $str .= $this->add_attributes($attr_ar);
        }
        $str .= '>';
        return $str;
    }
    
    function end_tag($tag='') {
        $str = $tag ? "</$tag>": "</$this->tag>";
        $this->tag = '';
        return $str;
    }
    
    function add_empty_tag($tag, $attr_ar=array()) {
        $str = "<$tag";
        if($attr_ar) {
            $str .= $this->add_attributes($attr_ar);
        }
        $str .= '>';
        return $str;
    }

    function end_form() {
        return "</form>";
    }

    function set_value($name, $value) {
        $this->form[$name]['value'] = $value;
    }

    function set_values($values) {
        foreach($values as $name => $value) {
            if(isset($this->form[$name]) && $this->form[$name]['type'] != 'submit')
                $this->form[$name]['value'] = $value;
        }
    }

    function set_error($name, $error) {
        $this->form[$name]['invalid'] = $error;
    }

    function check_errors() {
        $form_valid = true;
        foreach($this->form as $field) {
            if(isset($field['required']) && $field['required'] && !$field['value'])
                $field['invalid'] = $field['error'] ?: $field['label'] . ' is required.';
            if($field['invalid'])
                $form_valid = false;
        }
        return !$form_valid;
    }

    function set_success_msg($msg) {
        $this->success_msg = $msg;
    }

    function get_html($action, $method='post', $new_line=true) {
        $c = new db_connection();
        $sql = "SELECT city FROM service_provider";
        $option_list = $c->query($sql);
        $cities_arr = array();
        foreach($option_list as $key => $value) {
            $cities_arr[$value[key($value)]] = $value[key($value)]; 
        }
        $str = $this->start_form($action, $method);
        if(!empty($this->success_msg))
            $str .= $this->success_msg;
        foreach($this->form as $field) {
            $checked = '';
            # TODO: better checking for editing profile
            if(isset($_SESSION['user']) and $this->form_name == 'edit_profile') {
                $user = $_SESSION['user'];
                # TODO: password unhashing
                if($field['type'] != 'radio')
                    $field['value'] = isset($user[$field['name']]) ? $user[$field['name']] : $field['value'];
                else
                    $checked =  $user[$field['name']] == $field['value']  ? 'checked' : '';
            }
            $str .= $field['label'];
            if($field['type'] == 'submit')
                $str .= $this->add_button($field['type'], $field['name'], $field['value']);
            else if($field['type'] == 'select') {
                $str .= $this->add_select_list($field['name'], $cities_arr);
            }
            else if($field['type'] == 'textarea') {
                $str .= $this->add_textarea($field['name']);
            }
            else if($field['type'] == 'radio') {
                foreach ($field['values'] as $value => $label) {
                    $str .= '<br>' . $this->add_input($field['type'], $field['name'], $value, array('required' => $field['required'], 'checked' => $value == $field['value'])) . ' ' . $label; 
                }
            }
            else 
                $str .= $this->add_input($field['type'], $field['name'], $field['value'], array('required' => $field['required'], $checked => $checked));
            if($field['invalid']) {
                $str .= '<div class="error">' . $field['invalid'] . '</div>';
            }
            if($new_line)
                $str .= '<br>';
        }
        $str .= $this->end_form();
        return $str;
    }
}