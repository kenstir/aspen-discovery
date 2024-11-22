import {Button} from 'native-base';
import React from 'react';
import {navigate} from '../../../helpers/RootNavigator';

export const StartLocalIllRequest = (props) => {
	const openLocalIllRequest = () => {
		navigate('CreateLocalIllRequest', {
			id: props.record,
			workTitle: props.workTitle
		});
	};

	return (
		<Button
			size="md"
			colorScheme="primary"
			variant="solid"
			_text={{
				padding: 0,
				textAlign: 'center',
			}}
			style={{
				flex: 1,
				flexWrap: 'wrap',
			}}
			onPress={openLocalIllRequest}>
			Request
		</Button>
	);
};