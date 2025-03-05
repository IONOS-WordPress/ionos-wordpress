import React from 'react';
import { Button } from '@wordpress/components';

function MyButton(props) {
  const {
    variant = 'primary',
    label = 'Hello world',
    disabled = false,
    // eslint-disable-next-line no-console
    onClick = () => console.log(`Button(label='${label}') was clicked`),
  } = props;

  return (
    <Button
      variant={variant}
      disabled={disabled}
      onClick={onClick}>
      {label}
    </Button>
  );
}

export default MyButton;
