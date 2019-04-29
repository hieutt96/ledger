<?php

return 
	
	[
		\App\Exceptions\AppException::ERR_NONE => 'Thành công',
		\App\Exceptions\AppException::ERR_ACCOUNT_NOT_FOUND => 'Không tìm thấy tài khoản',
		\App\Exceptions\AppException::ERR_SYSTEM => 'Lỗi hệ thống',
		\App\Exceptions\AppException::ERR_SIGNATURE => 'Sai chữ kí số',
		\App\Exceptions\AppException::ERR_MOMO_NOT_ERRCODE => 'Tham số trả về không đúng. Giao dịch thất bại',
		\App\Exceptions\AppException::ERR_PASSWORD_INVAILD => 'Sai mật khẩu đăng nhập',
		\App\Exceptions\AppException::ERR_OVER_BALANCE => 'Số tiền vượt quá số dư',
		\App\Exceptions\AppException::ERR_OVER_TXN => 'Số tiền vượt quá giới hạn giao dịch',
		\App\Exceptions\AppException::ERR_DOWN_TXN => 'Số tiền vượt nhỏ hơn hạn giao dịch',
			
	];
