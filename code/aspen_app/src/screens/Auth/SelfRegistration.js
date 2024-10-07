import React from 'react';
import _ from 'lodash';
import { useRoute, useNavigation } from '@react-navigation/native';
import { loadError } from '../../components/loadError';
import { loadingSpinner } from '../../components/loadingSpinner';
import { LibrarySystemContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { getBasicRegistrationForm, submitBasicRegistration } from '../../util/api/library';
import { Platform } from 'react-native';

import { useKeyboard } from '../../util/useKeyboard';
import { ScrollView, Box, Button, Center, FormControl, Input, Text } from 'native-base';

export const SelfRegistration = () => {
	const route = useRoute();
	const navigation = useNavigation();
	const libraryUrl = route?.params?.libraryUrl ?? '';
	const [isLoading, setIsLoading] = React.useState(true);
	const [fields, setFields] = React.useState([]);
	const [firstName, setFirstName] = React.useState('');
	const [lastName, setLastName] = React.useState('');
	const [address, setAddress] = React.useState('');
	const [address2, setAddress2] = React.useState('');
	const [city, setCity] = React.useState('');
	const [state, setState] = React.useState('');
	const [zipCode, setZipCode] = React.useState('');
	const [phone, setPhone] = React.useState('');
	const [email, setEmail] = React.useState('');
	const [isSubmitting, setIsSubmitting] = React.useState(false);

	React.useEffect(() => {
		(async () => {
			await getBasicRegistrationForm(libraryUrl).then((fields) => {
				setFields(fields);
			});
			setIsLoading(false);
		})();
	}, []);

	const getFirstNameField = () => {
		const field = _.find(fields, ['property', 'firstname']);
		if (!_.isEmpty(field)) {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						accessibilityLabel={field.label}
						onChangeText={(value) => {
							setFirstName(value);
						}}
					/>
				</FormControl>
			);
		}
		return null;
	};

	const getLastNameField = () => {
		const field = _.find(fields, ['property', 'lastname']);
		if (!_.isEmpty(field)) {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						accessibilityLabel={field.label}
						onChangeText={(value) => {
							setLastName(value);
						}}
					/>
				</FormControl>
			);
		}
		return null;
	};

	const getAddressField = () => {
		const field = _.find(fields, ['property', 'address']);
		if (!_.isEmpty(field)) {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						accessibilityLabel={field.label}
						onChangeText={(value) => {
							setAddress(value);
						}}
					/>
				</FormControl>
			);
		}
		return null;
	};

	const getAddress2Field = () => {
		const field = _.find(fields, ['property', 'address2']);
		if (!_.isEmpty(field)) {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						accessibilityLabel={field.label}
						onChangeText={(value) => {
							setAddress2(value);
						}}
					/>
				</FormControl>
			);
		}
		return null;
	};

	const getStateField = () => {
		const field = _.find(fields, ['property', 'state']);
		if (!_.isEmpty(field)) {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						accessibilityLabel={field.label}
						onChangeText={(value) => {
							setState(value);
						}}
					/>
				</FormControl>
			);
		}
		return null;
	};

	const getCityField = () => {
		const field = _.find(fields, ['property', 'city']);
		if (!_.isEmpty(field)) {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						accessibilityLabel={field.label}
						onChangeText={(value) => {
							setCity(value);
						}}
					/>
				</FormControl>
			);
		}
		return null;
	};

	const getZipCodeField = () => {
		const field = _.find(fields, ['property', 'zipcode']);
		if (!_.isEmpty(field)) {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						accessibilityLabel={field.label}
						onChangeText={(value) => {
							setZipCode(value);
						}}
					/>
				</FormControl>
			);
		}
		return null;
	};

	const getPhoneField = () => {
		const field = _.find(fields, ['property', 'phone']);
		if (!_.isEmpty(field)) {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						accessibilityLabel={field.label}
						onChangeText={(value) => {
							setPhone(value);
						}}
						textContentType="phone"
					/>
				</FormControl>
			);
		}
		return null;
	};

	const getEmailField = () => {
		const field = _.find(fields, ['property', 'email']);
		if (!_.isEmpty(field)) {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						accessibilityLabel={field.label}
						onChangeText={(value) => {
							setEmail(value);
						}}
						textContentType="emailAddress"
					/>
				</FormControl>
			);
		}
		return null;
	};

	const handleSubmission = async () => {
		const data = {
			firstname: firstName ?? null,
			lastname: lastName ?? null,
			address: address ?? null,
			address2: address2 ?? null,
			city: city ?? null,
			state: state ?? null,
			zipcode: zipCode ?? null,
			phone: phone ?? null,
			email: email ?? null,
		};
		await submitBasicRegistration(libraryUrl, data).then((result) => {
			setIsSubmitting(false);
		});
	};

	return (
		<>
			{isLoading ? (
				loadingSpinner()
			) : (
				<ScrollView>
					<Box safeArea={5}>
						{getFirstNameField()}
						{getLastNameField()}
						{getAddressField()}
						{getAddress2Field()}
						{getCityField()}
						{getStateField()}
						{getZipCodeField()}
						{getPhoneField()}
						{getEmailField()}
						<Button.Group pt={3}>
							<Button
								colorScheme="secondary"
								isLoading={isSubmitting}
								isLoadingText="Creating"
								onPress={() => {
									setIsSubmitting(true);
									handleSubmission();
								}}>
								{getTermFromDictionary('en', 'register')}
							</Button>
							<Button colorScheme="secondary" variant="outline" onPress={() => navigation.goBack()}>
								{getTermFromDictionary('en', 'cancel')}
							</Button>
						</Button.Group>
					</Box>
				</ScrollView>
			)}
		</>
	);
};