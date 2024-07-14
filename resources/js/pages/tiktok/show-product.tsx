import React, {FormEventHandler} from 'react'
import {Head, useForm} from '@inertiajs/react'
import GuestLayout from "@/layouts/guest";
import TextInput from "@/components/form/text-input";
import {Button} from "@/components/ui/button";
import {IconLoader3, IconPumpkinScary} from "@tabler/icons-react";

const ShowProduct = ({}) => {

	const {data, setData, post, processing, errors, reset} = useForm({
		url: '',
	})

	const submit: FormEventHandler = (e) => {
		e.preventDefault();
		post(route('tiktok.product-scrapper'));
	}
	return (
		<div>
			<Head title={'Tiktok'} />
			<form onSubmit={submit}>
				<div className="form-group">
					<label htmlFor="url">Link Tiktok Shop</label>
					<TextInput
						name={'url'}
						type={'text'}
						placeholder={'Masukan Link'}
						onChange={(e) => setData('url', e.target.value)}
						value={data.url}
						errors={errors.url}
					/>
				</div>
				<div className="form-group grid">
					<Button disabled={processing}>
						{processing ? (
							<>
								<IconLoader3 className="animate-spin" />
								Sedang Mencari
							</>
						) : (
							<>
								<IconPumpkinScary />
								Scrap
							</>
						)}
					</Button>
				</div>
			</form>
		</div>
	)
}
ShowProduct.layout = (page: React.ReactNode) => <GuestLayout children={page} />
export default ShowProduct
