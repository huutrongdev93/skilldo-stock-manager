<?php

class SkdepotMemberAdminAjax
{
    static function add(\SkillDo\Http\Request $request): void
    {
        if(!Auth::hasCap('create_users')) {
            response()->error(trans('user.ajax.role'));
        }

        $form = Cms::get()->creatForm(['class' => 'member']);

        $validations = $request->validate($form);

        if($validations->fails()) {
            response()->error($validations->errors());
        }

        $userCreated    = [];

        $userMetaData   = [];

        $userCreated['username'] = $request->input('username');

        $userCreated['firstname'] = $request->input('firstname');

        $userCreated['lastname'] = $request->input('lastname');

        $userCreated['phone'] = $request->input('phone');

        $userCreated['email'] = $request->input('email');

        $userCreated['password'] = $request->input('password');

        if (!empty($request->input('address'))) {
            $userMetaData['address'] = $request->input('address');
        }

        if (!empty($request->input('city'))) {
            $userMetaData['city'] = $request->input('city');
        }

        if (!empty($request->input('districts'))) {
            $userMetaData['districts'] = $request->input('districts');
        }

        if (!empty($request->input('ward'))) {
            $userMetaData['ward'] = $request->input('ward');
        }

        $error = apply_filters('admin_registration_errors', [], $userCreated, $userMetaData);

        if(is_skd_error($error)) {
            response()->error($error);
        }

        $userCreated['status'] = 'public';

        $userCreated = apply_filters('admin_pre_user_register', $userCreated, $request);

        $userMetaData = apply_filters('admin_pre_user_register_meta', $userMetaData, $request);

        $userCreated['isMember'] = 1;

        $error = User::create($userCreated);

        if(is_skd_error($error))
        {
            response()->error($error);
        }

        if(have_posts($userMetaData)) {
            foreach ($userMetaData as $metaKey => $metaValue) {
                if (!empty($metaValue)) {
                    User::updateMeta($error, $metaKey, $metaValue);
                }
            }
        }

        do_action('admin_user_registration_success', $error, $userCreated, $userMetaData);

        response()->success(trans('user.ajax.add.success'));
    }

    static function save(\SkillDo\Http\Request $request): void
    {
        $id = (int)$request->input('id');

        $userCurrent = Auth::user();

        $userEdit = User::find($id);

        if(!have_posts($userEdit)) {
            response()->error(trans('user.ajax.noExit'));
        }

        if($userCurrent->id != $userEdit->id && !Auth::hasCap('edit_users')) {
            response()->error(trans('user.ajax.role'));
        }

        $form = AdminUserForm::setting($userEdit);

        $validations = $request->validate($form);

        if($validations->fails()) {
            response()->error($validations->errors());
        }

        $error          = [];
        $userMetaData   = [];
        $userUpdate = ['id' => $userEdit->id];
        $userUpdate['firstname'] = $request->input('firstname');
        $userUpdate['lastname']  = $request->input('lastname');
        $userUpdate['phone']     = $request->input('phone');
        $userUpdate['email']     = $request->input('email');

        if (!empty($request->input('address'))) {
            $userMetaData['address'] = $request->input('address');
        }

        if (!empty($request->input('city')))
        {
            $userMetaData['city'] = $request->input('city');

            if (!empty($request->input('districts')))
            {
                $userMetaData['districts'] = $request->input('districts');

                if (!empty($request->input('ward')))
                {
                    $userMetaData['ward'] = $request->input('ward');
                }
            }
        }

        $error = apply_filters('admin_user_profile_errors', $error, $userUpdate, $userMetaData );

        if($error instanceof SKD_Error) {
            response()->error($error);
        }

        $userUpdate = apply_filters('edit_user_update_profile', $userUpdate, $userEdit);

        $userMetaData = apply_filters('edit_user_update_profile_meta', $userMetaData, $userEdit);

        /**
         * @singe version 7.0.3
         */
        $userUpdate = apply_filters('admin_pre_user_update', $userUpdate, $request, $userEdit);

        /**
         * @singe version 7.0.3
         */
        $userMetaData = apply_filters('admin_pre_user_update_meta', $userMetaData, $request, $userEdit);

        try {

            $error = User::insert($userUpdate, $userEdit);

            if(is_skd_error($error)) {
                response()->error($error);
            }

            if(have_posts($userMetaData)) {
                foreach ($userMetaData as $metaKey => $metaValue) {
                    User::updateMeta($userEdit->id, $metaKey, $metaValue);
                }
            }

            do_action('edit_user_update_profile', $userUpdate, $userMetaData);

            /**
             * @singe version 7.0.3
             */
            do_action('admin_user_update_success', $error, $userUpdate, $userMetaData);

            response()->success(trans('ajax.update.success'));

        }
        catch (Exception $e) {
            \SkillDo\Log::error("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine(), (array)$e->getTraceAsString());
        }

        response()->error(trans('ajax.update.error'));
    }
}

Ajax::admin('SkdepotMemberAdminAjax::add');
Ajax::admin('SkdepotMemberAdminAjax::save');